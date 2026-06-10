<?php

declare(strict_types=1);

namespace ErdmannFreunde\ContaoStatusUpdateBundle\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renders active frontend status updates.
 */
#[AsFrontendModule(
    type: 'status_updates',
    category: 'miscellaneous',
)]
class StatusUpdatesModuleController extends AbstractFrontendModuleController
{
    /**
     * Sort priority for the `type` field. Lower index = rendered first.
     */
    private const TYPE_PRIORITY = ['critical', 'warning', 'info', 'success', 'promo', 'neutral'];

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $pageTrail = $this->getCurrentPageTrail($request);
        $now = strtotime(date('Y-m-d 00:00:00'));

        $typePriority = "'" . implode("','", self::TYPE_PRIORITY) . "'";

        $rows = $this->connection->fetchAllAssociative(
            "SELECT * FROM tl_status_update
             WHERE published = 1
               AND completed = 0
               AND scope IN ('frontend', 'both')
               AND date <= :now
               AND (date_end IS NULL OR date_end >= :now)
             ORDER BY FIELD(type, $typePriority), date ASC",
            ['now' => $now]
        );

        $items = [];
        foreach ($rows as $row) {
            if (!$this->matchesPage($row, $pageTrail)) {
                continue;
            }

            $type = (string) ($row['type'] ?: 'info');

            $items[] = [
                'id' => (int) $row['id'],
                'tstamp' => (int) $row['tstamp'],
                'type' => $type,
                'title' => (string) $row['title'],
                'description' => (string) ($row['description'] ?? ''),
                'dismissible' => !empty($row['dismissible']),
                'date' => (int) $row['date'],
                'date_end' => (int) $row['date_end'],
                'cssClass' => 'status-update status-update--' . $type,
            ];
        }

        if ($items === []) {
            return new Response('');
        }

        $template->set('items', $items);

        return $template->getResponse();
    }

    /**
     * @return list<int> all page IDs from the current page up to root (inclusive)
     */
    private function getCurrentPageTrail(Request $request): array
    {
        $pageModel = $request->attributes->get('pageModel');

        if (!$pageModel instanceof PageModel) {
            return [];
        }

        $pageModel->loadDetails();
        $trail = array_map('intval', $pageModel->trail ?: []);

        if ($trail === [] || !\in_array((int) $pageModel->id, $trail, true)) {
            $trail[] = (int) $pageModel->id;
        }

        return $trail;
    }

    /**
     * @param array<string, mixed> $row
     * @param list<int>            $pageTrail
     */
    private function matchesPage(array $row, array $pageTrail): bool
    {
        $allowedPages = array_filter(array_map('intval', StringUtil::deserialize($row['frontend_pages'] ?? null, true)));

        if ($allowedPages === []) {
            return true;
        }

        if ($pageTrail === []) {
            return false;
        }

        return array_intersect($pageTrail, $allowedPages) !== [];
    }
}

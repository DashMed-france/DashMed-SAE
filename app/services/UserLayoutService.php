<?php

declare(strict_types=1);

namespace modules\services;

use modules\models\repositories\MonitorPreferenceRepository;

/**
 * Class UserLayoutService
 *
 * Service managing user layout (GridStack layout).
 * Handles widget arrangement, visibility, and persistence.
 *
 * @package DashMed\Modules\Services
 * @author DashMed Team
 * @license Proprietary
 */
final class UserLayoutService
{
    private const GRID_COLUMNS = 12;
    private const DEFAULT_WIDTH = 4;
    private const DEFAULT_HEIGHT = 3;
    private const MIN_WIDTH = 4;
    private const MIN_HEIGHT = 3;
    private const MAX_HEIGHT = 10;
    private const WIDGETS_PER_ROW = 3;

    /** @var MonitorPreferenceRepository Preference model */
    private MonitorPreferenceRepository $prefModel;

    /**
     * Constructor.
     *
     * @param MonitorPreferenceRepository $prefModel
     */
    public function __construct(MonitorPreferenceRepository $prefModel)
    {
        $this->prefModel = $prefModel;
    }

    /**
     * Builds the list of visible and hidden widgets for customization.
     *
     * @param int $userId User ID.
     * @return array{widgets: list<array>, hidden: list<array>} Arrays of widgets
     */
    public function buildWidgetsForCustomization(int $userId): array
    {
        $allParams = $this->prefModel->getAllParameters();
        $userLayout = $this->prefModel->getUserLayoutSimple($userId);

        $layoutMap = [];
        foreach ($userLayout as $row) {
            $layoutMap[(string) $row['parameter_id']] = $row;
        }

        $widgets = [];
        $hidden = [];
        $col = 0;

        foreach ($allParams as $idx => $param) {
            $pid = $param['parameter_id'];
            $saved = $layoutMap[$pid] ?? null;

            $item = [
                'id' => $pid,
                'name' => $param['display_name'],
                'category' => $param['category'],
                'x' => $saved !== null
                    ? (int) $saved['grid_x']
                    : (($col % self::WIDGETS_PER_ROW) * self::DEFAULT_WIDTH),
                'y' => $saved !== null
                    ? (int) $saved['grid_y']
                    : ((int) floor($col / self::WIDGETS_PER_ROW) * self::DEFAULT_HEIGHT),
                'w' => $saved !== null
                    ? max(self::MIN_WIDTH, (int) $saved['grid_w'])
                    : self::DEFAULT_WIDTH,
                'h' => $saved !== null
                    ? max(self::MIN_HEIGHT, (int) $saved['grid_h'])
                    : self::DEFAULT_HEIGHT,
                'is_hidden' => $saved !== null ? (bool) $saved['is_hidden'] : false,
                'display_order' => $saved !== null ? (int) $saved['display_order'] : $idx + 1,
            ];

            if ($saved === null) {
                $col++;
            }

            if ($item['is_hidden']) {
                $hidden[] = $item;
            } else {
                $widgets[] = $item;
            }
        }

        usort($widgets, static fn(array $a, array $b): int => $a['display_order'] <=> $b['display_order']);

        return ['widgets' => $widgets, 'hidden' => $hidden];
    }

    /**
     * Validates and parses layout data received from the form.
     *
     * @param string $jsonData Raw JSON data
     * @return list<array{id: string, x: int, y: int, w: int, h: int, visible: bool}> Parsed items
     * @throws \InvalidArgumentException If JSON is invalid
     */
    public function validateAndParseLayoutData(string $jsonData): array
    {
        if ($jsonData === '') {
            return [];
        }

        $items = json_decode($jsonData, true);

        if (!is_array($items)) {
            throw new \InvalidArgumentException('Les données du layout ne sont pas un tableau JSON valide');
        }

        $validatedItems = [];

        foreach ($items as $item) {
            if (!$this->isValidLayoutItem($item)) {
                continue;
            }

            /** @var array{id: string, x: numeric, y: numeric, w: numeric, h: numeric, visible?: bool} $item */
            $validatedItems[] = [
                'id' => (string) $item['id'],
                'x' => max(0, min(self::GRID_COLUMNS - 1, (int) $item['x'])),
                'y' => max(0, (int) $item['y']),
                'w' => max(self::MIN_WIDTH, min(self::GRID_COLUMNS, (int) $item['w'])),
                'h' => max(self::MIN_HEIGHT, min(self::MAX_HEIGHT, (int) $item['h'])),
                'visible' => (bool) ($item['visible'] ?? true),
            ];
        }

        return $validatedItems;
    }

    /**
     * Verifies if a layout item has a valid structure.
     *
     * @param mixed $item Item to check
     * @return bool Validity
     */
    private function isValidLayoutItem(mixed $item): bool
    {
        if (!is_array($item)) {
            return false;
        }

        if (!isset($item['id']) || !is_string($item['id']) || $item['id'] === '') {
            return false;
        }

        $requiredNumeric = ['x', 'y', 'w', 'h'];
        foreach ($requiredNumeric as $key) {
            if (!isset($item[$key]) || !is_numeric($item[$key])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Saves the user layout.
     *
     * @param int $userId User ID.
     * @param array<int, array{
     *   id: string,
     *   x: int,
     *   y: int,
     *   w: int,
     *   h: int,
     *   visible: bool
     * }> $layoutItems Layout items
     * @return void
     */
    public function saveLayout(int $userId, array $layoutItems): void
    {
        $this->prefModel->saveUserLayoutSimple($userId, $layoutItems);
    }

    /**
     * Resets the user layout.
     *
     * @param int $userId User ID
     * @return void
     */
    public function resetLayout(int $userId): void
    {
        $this->prefModel->resetUserLayoutSimple($userId);
    }

    /**
     * Retrieves the layout for dashboard display.
     *
     * @param int $userId User ID.
     * @return array<string, array{
     *   parameter_id: string,
     *   display_order: int,
     *   is_hidden: int,
     *   grid_x: int,
     *   grid_y: int,
     *   grid_w: int,
     *   grid_h: int
     * }> Layout map
     */
    public function getLayoutMapForDashboard(int $userId): array
    {
        $layout = $this->prefModel->getUserLayoutSimple($userId);

        $map = [];
        foreach ($layout as $row) {
            $map[(string) $row['parameter_id']] = $row;
        }

        return $map;
    }
}

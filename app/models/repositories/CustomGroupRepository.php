<?php

declare(strict_types=1);

namespace modules\models\repositories;

use modules\models\BaseRepository;
use PDO;

/**
 * Class CustomGroupRepository
 *
 * Manages custom indicator groups created by users.
 *
 * @package DashMed\Modules\Models\Repositories
 * @author DashMed Team
 * @license Proprietary
 */
class CustomGroupRepository extends BaseRepository
{
    /**
     * Constructor
     *
     * @param PDO|null $pdo
     */
    public function __construct(?PDO $pdo = null)
    {
        parent::__construct($pdo);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    /**
     * Creates a custom group for a user.
     *
     * @param int    $userId
     * @param string $name
     * @param string $color
     * @return int Inserted group ID
     */
    public function createGroup(int $userId, string $name, string $color = '#3b82f6'): int
    {
        $st = $this->pdo->prepare(
            'INSERT INTO custom_groups (name, user_id, color) VALUES (:name, :user_id, :color)'
        );
        $st->execute([':name' => $name, ':user_id' => $userId, ':color' => $color]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Adds an indicator to a group.
     *
     * @param int    $groupId
     * @param string $parameterId
     * @return void
     */
    public function addIndicator(int $groupId, string $parameterId): void
    {
        $st = $this->pdo->prepare(
            'INSERT IGNORE INTO custom_group_indicators (group_id, indicator_id)
             VALUES (:group_id, :indicator_id)'
        );
        $st->execute([':group_id' => $groupId, ':indicator_id' => $parameterId]);
    }

    /**
     * Returns all groups belonging to a user.
     *
     * @param int $userId
     * @return array<int, array{id: int, name: string, color: string, created_at: string}>
     */
    public function getGroupsByUser(int $userId): array
    {
        $st = $this->pdo->prepare(
            'SELECT id, name, color, created_at
             FROM custom_groups
             WHERE user_id = :user_id
             ORDER BY created_at ASC'
        );
        $st->execute([':user_id' => $userId]);
        /** @var array<int, array{id: int, name: string, color: string, created_at: string}> */
        return $st->fetchAll();
    }

    /**
     * Returns a group by its ID.
     *
     * @param int $groupId
     * @param int $userId
     * @return array{id: int, name: string, color: string}|null
     */
    public function getGroupById(int $groupId, int $userId): ?array
    {
        $st = $this->pdo->prepare(
            'SELECT id, name, color
             FROM custom_groups
             WHERE id = :id AND user_id = :user_id'
        );
        $st->execute([':id' => $groupId, ':user_id' => $userId]);
        $row = $st->fetch();
        return $row ?: null;
    }

    /**
     * Returns indicator IDs for a given group.
     *
     * @param int $groupId
     * @return array<int, string>
     */
    public function getIndicatorsByGroup(int $groupId): array
    {
        $st = $this->pdo->prepare(
            'SELECT indicator_id
             FROM custom_group_indicators
             WHERE group_id = :group_id'
        );
        $st->execute([':group_id' => $groupId]);
        return array_column($st->fetchAll(), 'indicator_id');
    }

    /**
     * Deletes a group (only if it belongs to the user).
     *
     * @param int $groupId
     * @param int $userId
     * @return void
     */
    public function deleteGroup(int $groupId, int $userId): void
    {
        $st = $this->pdo->prepare(
            'DELETE FROM custom_groups WHERE id = :id AND user_id = :user_id'
        );
        $st->execute([':id' => $groupId, ':user_id' => $userId]);
    }

    /**
     * Checks if a group name already exists for the user.
     *
     * @param int    $userId
     * @param string $name
     * @return bool
     */
    public function groupNameExists(int $userId, string $name): bool
    {
        $st = $this->pdo->prepare(
            'SELECT COUNT(*) FROM custom_groups WHERE user_id = :user_id AND name = :name'
        );
        $st->execute([':user_id' => $userId, ':name' => $name]);
        return (int) $st->fetchColumn() > 0;
    }

    /**
     * Returns all available parameter references (id + display_name + category).
     *
     * @return array<int, array{parameter_id: string, display_name: string, category: string}>
     */
    public function getAllParameterReferences(): array
    {
        $st = $this->pdo->prepare(
            'SELECT parameter_id, display_name, category
             FROM parameter_reference
             ORDER BY category ASC, display_name ASC'
        );
        $st->execute();
        /** @var array<int, array{parameter_id: string, display_name: string, category: string}> */
        return $st->fetchAll();
    }

    /**
     * Updates group details and its indicators.
     *
     * @param int $groupId
     * @param int $userId
     * @param string $name
     * @param string $color
     * @param array<string> $indicatorIds
     * @return void
     */
    public function updateGroup(int $groupId, int $userId, string $name, string $color, array $indicatorIds): void
    {
        // Check standard ownership and update name/color
        $st = $this->pdo->prepare(
            'UPDATE custom_groups SET name = :name, color = :color WHERE id = :id AND user_id = :user_id'
        );
        $st->execute([':name' => $name, ':color' => $color, ':id' => $groupId, ':user_id' => $userId]);

        // If not modified, maybe it doesn't belong to the user. Stop here if no rows affected? 
        if ($st->rowCount() === 0 && !$this->groupExistsForUser($groupId, $userId)) {
            return;
        }

        $existing = $this->getIndicatorsByGroup($groupId);

        $toDelete = array_diff($existing, $indicatorIds);
        if (!empty($toDelete)) {
            $inQuery = implode(',', array_fill(0, count($toDelete), '?'));
            $stDel = $this->pdo->prepare("DELETE FROM custom_group_indicators WHERE group_id = ? AND indicator_id IN ($inQuery)");
            $stDel->execute(array_merge([$groupId], $toDelete));
        }

        $toInsert = array_diff($indicatorIds, $existing);
        foreach ($toInsert as $pid) {
            $this->addIndicator($groupId, $pid);
        }
    }

    private function groupExistsForUser(int $groupId, int $userId): bool
    {
        $st = $this->pdo->prepare('SELECT 1 FROM custom_groups WHERE id = :id AND user_id = :uid');
        $st->execute([':id' => $groupId, ':uid' => $userId]);
        return (bool) $st->fetchColumn();
    }

    /**
     * Returns indicators with their layout, falling back to general disposition.
     *
     * @param int $groupId
     * @param int $userId
     * @return array<int, array>
     */
    public function getGroupIndicatorsWithLayout(int $groupId, int $userId): array
    {
        $st = $this->pdo->prepare(
            'SELECT i.indicator_id AS id, p.display_name AS name, p.category,
                    i.grid_x, i.grid_y, i.grid_w, i.grid_h,
                    u.grid_x AS def_x, u.grid_y AS def_y, u.grid_w AS def_w, u.grid_h AS def_h
             FROM custom_group_indicators i
             JOIN parameter_reference p ON i.indicator_id = p.parameter_id
             LEFT JOIN user_parameter_order u ON u.parameter_id = i.indicator_id AND u.id_user = :user_id
             WHERE i.group_id = :group_id'
        );
        $st->execute([':group_id' => $groupId, ':user_id' => $userId]);

        $results = $st->fetchAll();
        $final = [];
        foreach ($results as $row) {
            $x = $row['grid_x'] !== null ? $row['grid_x'] : $row['def_x'];
            $y = $row['grid_y'] !== null ? $row['grid_y'] : $row['def_y'];
            $w = $row['grid_w'] !== null ? $row['grid_w'] : $row['def_w'];
            $h = $row['grid_h'] !== null ? $row['grid_h'] : $row['def_h'];

            // Fallback grid dimension to min bounds if all are null (e.g parameter never added to main layout)
            if ($w === null)
                $w = 4;
            if ($h === null)
                $h = 3;
            // grid_x and grid_y can be null, gridstack will position them implicitly.

            $final[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'category' => $row['category'],
                'x' => $x !== null ? (int) $x : null,
                'y' => $y !== null ? (int) $y : null,
                'w' => (int) $w,
                'h' => (int) $h
            ];
        }
        return $final;
    }

    /**
     * Save group-specific widget layout
     * 
     * @param int $groupId
     * @param array<int, array{id: string, x: int, y: int, w: int, h: int}> $layoutItems
     */
    public function saveGroupLayout(int $groupId, array $layoutItems): void
    {
        $st = $this->pdo->prepare(
            'UPDATE custom_group_indicators 
             SET grid_x = :x, grid_y = :y, grid_w = :w, grid_h = :h 
             WHERE group_id = :group_id AND indicator_id = :indicator_id'
        );
        foreach ($layoutItems as $item) {
            $st->execute([
                ':x' => $item['x'],
                ':y' => $item['y'],
                ':w' => $item['w'],
                ':h' => $item['h'],
                ':group_id' => $groupId,
                ':indicator_id' => $item['id'],
            ]);
        }
    }
}

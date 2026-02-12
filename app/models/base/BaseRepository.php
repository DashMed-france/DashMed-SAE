<?php

declare(strict_types=1);

namespace modules\models\base;

use assets\includes\Database;
use PDO;

/**
 * Class BaseRepository | Classe Abstraite BaseRepository
 *
 * Parent class for all repositories. Centralizes PDO connection management.
 * Classe parente de tous les repositories. Centralise la gestion de la connexion PDO.
 *
 * @package DashMed\Modules\Models\Base
 * @author DashMed Team
 * @license Proprietary
 */
abstract class BaseRepository
{
    /** @var PDO Database connection | Connexion à la base de données */
    protected PDO $pdo;

    /**
     * Constructor | Constructeur
     *
     * @param PDO|null $pdo Database connection (optional, defaults to singleton) |
     *                      Connexion BDD (optionnel, utilise le singleton par défaut)
     */
    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();
    }
}

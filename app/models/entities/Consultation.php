<?php

declare(strict_types=1);

namespace modules\models\entities;

use modules\models\interfaces\EntityInterface;

/**
 * Class Consultation | Classe Consultation
 *
 * Represents a medical consultation entity.
 * Représente une entité de consultation médicale.
 *
 * @package DashMed\Modules\Models\Entities
 * @author DashMed Team
 * @license Proprietary
 */
class Consultation implements EntityInterface
{
    /** @var int Consultation ID | Identifiant de la consultation */
    private int $id;

    /** @var int|string Doctor's user ID | Identifiant utilisateur du médecin */
    private int|string $idDoctor;

    /** @var string Doctor's name | Nom du médecin */
    private string $doctor;

    /** @var string Date of consultation | Date de la consultation */
    private string $date;

    /** @var string Title of the consultation | Titre de la consultation */
    private string $title;

    /** @var string Type of event/consultation | Type d'événement/consultation */
    private string $evenementType;

    /** @var string Note or report content | Contenu de la note ou du rapport */
    private string $note;

    /** @var string|null Associated document path or name | Chemin ou nom du document associé */
    private ?string $document;

    /**
     * Constructor | Constructeur
     *
     * @param int $id Consultation ID | ID Consultation
     * @param int|string $idDoctor Doctor ID or Name | ID ou Nom Médecin
     * @param string $doctor Doctor Name | Nom Médecin
     * @param string $date Date
     * @param string $title Title | Titre
     * @param string $evenementType Type
     * @param string $note Note
     * @param string|null $document Document
     */
    public function __construct(
        int $id,
        int|string $idDoctor,
        string $doctor,
        string $date,
        string $title,
        string $evenementType,
        string $note,
        ?string $document = null
    ) {
        $this->id = $id;
        $this->idDoctor = $idDoctor;
        $this->doctor = $doctor;
        $this->date = $date;
        $this->title = $title;
        $this->evenementType = $evenementType;
        $this->note = $note;
        $this->document = $document;
    }

    /**
     * Get ID | Obtenir l'ID
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get Doctor ID | Obtenir l'ID du médecin
     *
     * @return int|string
     */
    public function getDoctorId(): int|string
    {
        return $this->idDoctor;
    }

    /**
     * Get Doctor Name | Obtenir le nom du médecin
     *
     * @return string
     */
    public function getDoctor(): string
    {
        return $this->doctor;
    }

    /**
     * Get Date | Obtenir la date
     *
     * @return string
     */
    public function getDate(): string
    {
        return $this->date;
    }

    /**
     * Get Title | Obtenir le titre
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Get Type (Alias) | Obtenir le type (Alias)
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->evenementType;
    }

    /**
     * Get Event Type | Obtenir le type d'événement
     *
     * @return string
     */
    public function getEvenementType(): string
    {
        return $this->evenementType;
    }

    /**
     * Get Note | Obtenir la note
     *
     * @return string
     */
    public function getNote(): string
    {
        return $this->note;
    }

    /**
     * Get Document | Obtenir le document
     *
     * @return string|null
     */
    public function getDocument(): ?string
    {
        return $this->document;
    }

    /**
     * Set ID | Définir l'ID
     *
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * Set Doctor Name | Définir le nom du médecin
     *
     * @param string $doctor
     */
    public function setDoctor(string $doctor): void
    {
        $this->doctor = $doctor;
    }

    /**
     * Set Date | Définir la date
     *
     * @param string $date
     */
    public function setDate(string $date): void
    {
        $this->date = $date;
    }

    /**
     * Set Title | Définir le titre
     *
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * Set Type (Alias) | Définir le type (Alias)
     *
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->evenementType = $type;
    }

    /**
     * Set Event Type | Définir le type d'événement
     *
     * @param string $evenementType
     */
    public function setEvenementType(string $evenementType): void
    {
        $this->evenementType = $evenementType;
    }

    /**
     * Set Note | Définir la note
     *
     * @param string $note
     */
    public function setNote(string $note): void
    {
        $this->note = $note;
    }

    /**
     * Set Document | Définir le document
     *
     * @param string|null $document
     */
    public function setDocument(?string $document): void
    {
        $this->document = $document;
    }

    /**
     * {@inheritdoc}
     *
     * @return array{
     *   id: int,
     *   doctor: string,
     *   date: string,
     *   title: string,
     *   type: string,
     *   note: string,
     *   document: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'doctor' => $this->doctor,
            'date' => $this->date,
            'title' => $this->title,
            'type' => $this->evenementType,
            'note' => $this->note,
            'document' => $this->document
        ];
    }
}

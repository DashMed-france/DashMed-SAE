<?php

declare(strict_types=1);

namespace modules\models\entities;

use modules\models\interfaces\EntityInterface;

/**
 * Class Consultation
 *
 * Represents a medical consultation entity.
 *
 * @package DashMed\Modules\Models\Entities
 * @author DashMed Team
 * @license Proprietary
 */
class Consultation implements EntityInterface
{
    /** @var int Consultation ID */
    private int $id;

    /** @var int|string Doctor's user ID */
    private int|string $idDoctor;

    /** @var string Doctor's name */
    private string $doctor;

    /** @var string Date of consultation */
    private string $date;

    /** @var string Title of the consultation */
    private string $title;

    /** @var string Type of event/consultation */
    private string $evenementType;

    /** @var string Note or report content */
    private string $note;

    /** @var string|null Associated document path or name */
    private ?string $document;

    /**
     * Constructor
     *
     * @param int $id Consultation ID
     * @param int|string $idDoctor Doctor ID or Name
     * @param string $doctor Doctor Name
     * @param string $date Date
     * @param string $title Title
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
     * Get ID
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get Doctor ID
     *
     * @return int|string
     */
    public function getDoctorId(): int|string
    {
        return $this->idDoctor;
    }

    /**
     * Get Doctor Name
     *
     * @return string
     */
    public function getDoctor(): string
    {
        return $this->doctor;
    }

    /**
     * Get Date
     *
     * @return string
     */
    public function getDate(): string
    {
        return $this->date;
    }

    /**
     * Get Title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Get Type (Alias)
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->evenementType;
    }

    /**
     * Get Event Type
     *
     * @return string
     */
    public function getEvenementType(): string
    {
        return $this->evenementType;
    }

    /**
     * Get Note
     *
     * @return string
     */
    public function getNote(): string
    {
        return $this->note;
    }

    /**
     * Get Document
     *
     * @return string|null
     */
    public function getDocument(): ?string
    {
        return $this->document;
    }

    /**
     * Set ID
     *
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * Set Doctor Name
     *
     * @param string $doctor
     */
    public function setDoctor(string $doctor): void
    {
        $this->doctor = $doctor;
    }

    /**
     * Set Date
     *
     * @param string $date
     */
    public function setDate(string $date): void
    {
        $this->date = $date;
    }

    /**
     * Set Title
     *
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * Set Type (Alias)
     *
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->evenementType = $type;
    }

    /**
     * Set Event Type
     *
     * @param string $evenementType
     */
    public function setEvenementType(string $evenementType): void
    {
        $this->evenementType = $evenementType;
    }

    /**
     * Set Note
     *
     * @param string $note
     */
    public function setNote(string $note): void
    {
        $this->note = $note;
    }

    /**
     * Set Document
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

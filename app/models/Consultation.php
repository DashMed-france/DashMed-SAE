<?php

namespace modules\models;

/**
 * Class Consultation | Classe Consultation
 *
 * Represents a medical consultation entity.
 * Représente une entité de consultation médicale.
 *
 * @package DashMed\Modules\Models
 * @author DashMed Team
 * @license Proprietary
 */
class Consultation
{
    /** @var int Consultation ID | Identifiant de la consultation */
    private $id;

    /** @var int|string Doctor's user ID | Identifiant utilisateur du médecin */
    private $idDoctor;

    /** @var string Doctor's name | Nom du médecin */
    private $Doctor;

    /** @var string Date of consultation | Date de la consultation */
    private $Date;

    /** @var string Title of the consultation | Titre de la consultation */
    private $Title;

    /** @var string Type of event/consultation | Type d'événement/consultation */
    private $EvenementType;

    /** @var string Note or report content | Contenu de la note ou du rapport */
    private $note;

    /** @var string|null Associated document path or name | Chemin ou nom du document associé */
    private $Document;

    /**
     * Constructor | Constructeur
     *
     * @param int $id Consultation ID | ID Consultation
     * @param int|string $idDoctor Doctor ID or Name | ID ou Nom Médecin
     * @param string $Doctor Doctor Name | Nom Médecin
     * @param string $Date Date
     * @param string $Title Title | Titre
     * @param string $EvenementType Type
     * @param string $note Note
     * @param string|null $Document Document
     */
    public function __construct($id, $idDoctor, $Doctor, $Date, $Title, $EvenementType, $note, $Document = null)
    {
        $this->id = $id;
        $this->idDoctor = $idDoctor;
        $this->Doctor = $Doctor;
        $this->Date = $Date;
        $this->Title = $Title;
        $this->EvenementType = $EvenementType;
        $this->note = $note;
        $this->Document = $Document;
    }

    /**
     * Get ID | Obtenir l'ID
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get Doctor ID | Obtenir l'ID du médecin
     * @return int|string
     */
    public function getDoctorId()
    {
        return $this->idDoctor;
    }

    /**
     * Get Doctor Name | Obtenir le nom du médecin
     * @return string
     */
    public function getDoctor()
    {
        return $this->Doctor;
    }

    /**
     * Get Date | Obtenir la date
     * @return string
     */
    public function getDate()
    {
        return $this->Date;
    }

    /**
     * Get Title | Obtenir le titre
     * @return string
     */
    public function getTitle()
    {
        return $this->Title;
    }

    /**
     * Get Type (Alias) | Obtenir le type (Alias)
     * @return string
     */
    public function getType()
    {
        return $this->EvenementType;
    }

    /**
     * Get Event Type | Obtenir le type d'événement
     * @return string
     */
    public function getEvenementType()
    {
        return $this->EvenementType;
    }

    /**
     * Get Note | Obtenir la note
     * @return string
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Get Document | Obtenir le document
     * @return string|null
     */
    public function getDocument()
    {
        return $this->Document;
    }

    /**
     * Set ID | Définir l'ID
     * @param int $id
     * @return void
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * Set Doctor Name | Définir le nom du médecin
     * @param string $Doctor
     * @return void
     */
    public function setDoctor($Doctor): void
    {
        $this->Doctor = $Doctor;
    }

    /**
     * Set Date | Définir la date
     * @param string $Date
     * @return void
     */
    public function setDate($Date): void
    {
        $this->Date = $Date;
    }

    /**
     * Set Title | Définir le titre
     * @param string $Title
     * @return void
     */
    public function setTitle($Title): void
    {
        $this->Title = $Title;
    }

    /**
     * Set Type (Alias) | Définir le type (Alias)
     * @param string $Type
     * @return void
     */
    public function setType($Type): void
    {
        $this->EvenementType = $Type;
    }

    /**
     * Set Event Type | Définir le type d'événement
     * @param string $EvenementType
     * @return void
     */
    public function setEvenementType($EvenementType): void
    {
        $this->EvenementType = $EvenementType;
    }

    /**
     * Set Note | Définir la note
     * @param string $note
     * @return void
     */
    public function setNote($note): void
    {
        $this->note = $note;
    }

    /**
     * Set Document | Définir le document
     * @param string|null $Document
     * @return void
     */
    public function setDocument($Document): void
    {
        $this->Document = $Document;
    }

    /**
     * Get Consultation as array | Obtenir la consultation sous forme de tableau
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
    public function getConsultation(): array
    {
        return [
            'id' => $this->id,
            'doctor' => $this->Doctor,
            'date' => $this->Date,
            'title' => $this->Title,
            'type' => $this->EvenementType,
            'note' => $this->note,
            'document' => $this->Document
        ];
    }
}

<?php

namespace modules\models;

/**
 * Entity class representing a medical consultation.
 *
 * This class encapsulates all data related to a medical consultation
 * including doctor information, timing, type, and associated documentation.
 */
class Consultation
{
    /**
     * Consultation unique identifier.
     *
     * @var int
     */
    private $id;

    /**
     * Doctor's (user) unique identifier.
     *
     * @var int
     */
    private $idDoctor;

    /**
     * Doctor's name.
     *
     * @var string
     */
    private $Doctor;

    /**
     * Consultation date.
     *
     * @var string
     */
    private $Date;

    /**
     * Consultation title.
     *
     * @var string
     */
    private $Title;

    /**
     * Type of consultation or event.
     *
     * @var string
     */
    private $EvenementType;

    /**
     * Consultation notes or report.
     *
     * @var string
     */
    private $note;

    /**
     * Associated document filename.
     *
     * @var string|null
     */
    private $Document;

    /**
     * Constructor for Consultation.
     *
     * Initializes a consultation object with all required data.
     *
     * @param int $id Consultation unique identifier
     * @param int $idDoctor Doctor's (user) unique identifier
     * @param string $Doctor Doctor's name
     * @param string $Date Consultation date
     * @param string $Title Consultation title
     * @param string $EvenementType Type of consultation or event
     * @param string $note Consultation notes or report
     * @param string|null $Document Optional associated document filename
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
     * Gets the consultation ID.
     *
     * @return int Consultation unique identifier
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets the doctor's ID.
     *
     * @return int Doctor's (user) unique identifier
     */
    public function getDoctorId()
    {
        return $this->idDoctor;
    }

    /**
     * Gets the doctor's name.
     *
     * @return string Doctor's name
     */
    public function getDoctor()
    {
        return $this->Doctor;
    }

    /**
     * Gets the consultation date.
     *
     * @return string Consultation date
     */
    public function getDate()
    {
        return $this->Date;
    }

    /**
     * Gets the consultation title.
     *
     * @return string Consultation title
     */
    public function getTitle()
    {
        return $this->Title;
    }

    /**
     * Gets the consultation type.
     *
     * @return string Type of consultation or event
     */
    public function getType()
    {
        return $this->EvenementType;
    }

    /**
     * Gets the event type (alias for getType).
     *
     * @return string Type of consultation or event
     */
    public function getEvenementType()
    {
        return $this->EvenementType;
    }

    /**
     * Gets the consultation notes.
     *
     * @return string Consultation notes or report
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Gets the associated document filename.
     *
     * @return string|null Document filename or null if none
     */
    public function getDocument()
    {
        return $this->Document;
    }

    /**
     * Sets the consultation ID.
     *
     * @param int $id Consultation unique identifier
     * @return void
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Sets the doctor's name.
     *
     * @param string $Doctor Doctor's name
     * @return void
     */
    public function setDoctor($Doctor)
    {
        $this->Doctor = $Doctor;
    }

    /**
     * Sets the consultation date.
     *
     * @param string $Date Consultation date
     * @return void
     */
    public function setDate($Date)
    {
        $this->Date = $Date;
    }

    /**
     * Sets the consultation title.
     *
     * @param string $Title Consultation title
     * @return void
     */
    public function setTitle($Title)
    {
        $this->Title = $Title;
    }

    /**
     * Sets the consultation type.
     *
     * @param string $Type Type of consultation or event
     * @return void
     */
    public function setType($Type)
    {
        $this->EvenementType = $Type;
    }

    /**
     * Sets the event type (alias for setType).
     *
     * @param string $EvenementType Type of consultation or event
     * @return void
     */
    public function setEvenementType($EvenementType)
    {
        $this->EvenementType = $EvenementType;
    }

    /**
     * Sets the consultation notes.
     *
     * @param string $note Consultation notes or report
     * @return void
     */
    public function setNote($note)
    {
        $this->note = $note;
    }

    /**
     * Sets the associated document filename.
     *
     * @param string|null $Document Document filename
     * @return void
     */
    public function setDocument($Document)
    {
        $this->Document = $Document;
    }

    /**
     * Returns consultation data as an associative array.
     *
     * Provides a convenient way to access all consultation data
     * in array format for serialization or display purposes.
     *
     * @return array Associative array containing all consultation data
     */
    public function getConsultation()
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
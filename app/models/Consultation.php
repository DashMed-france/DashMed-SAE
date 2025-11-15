<?php

namespace modules\models;

class Consultation
{
    private $Doctor;
    private $Date;
    private $EvenementType;
    private $note;
    private $Document;

    public function __construct($Doctor, $Date, $EvenementType, $note, $Document)
    {
        $this->Doctor = $Doctor;
        $this->Date = $Date;
        $this->EvenementType = $EvenementType;
        $this->note = $note;
        $this->Document = $Document;
    }

    public function getDoctor()
    {
        return $this->Doctor;
    }

    public function getDate()
    {
        return $this->Date;
    }

    public function getEvenementType()
    {
        return $this->EvenementType;
    }

    public function getNote()
    {
        return $this->note;
    }

    public function getDocument()
    {
        return $this->Document;
    }

    public function setDoctor($Doctor)
    {
        $this->Doctor = $Doctor;
    }

    public function setDate($Date)
    {
        $this->Date = $Date;
    }

    public function setEvenementType($EvenementType)
    {
        $this->EvenementType = $EvenementType;
    }

    public function setNote($note)
    {
        $this->note = $note;
    }

    public function setDocument($Document)
    {
        $this->Document = $Document;
    }

    public function getConsultation()
    {
        return [
            'doctor' => $this->Doctor,
            'date' => $this->Date,
            'evenement_type' => $this->EvenementType,
            'note' => $this->note,
            'document' => $this->Document
        ];
    }
}

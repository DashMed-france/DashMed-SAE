<?php

namespace modules\models;

class Consultation
{
    private $id;
    private $Doctor;
    private $Date;
    private $Title;
    private $EvenementType;
    private $note;
    private $Document;

    public function __construct($id, $Doctor, $Date, $Title, $EvenementType, $note, $Document = null)
    {
        $this->id = $id;
        $this->Doctor = $Doctor;
        $this->Date = $Date;
        $this->Title = $Title;
        $this->EvenementType = $EvenementType;
        $this->note = $note;
        $this->Document = $Document;
    }

    public function getId()
    {
        return $this->id;
    }
    public function getDoctor()
    {
        return $this->Doctor;
    }
    public function getDate()
    {
        return $this->Date;
    }
    public function getTitle()
    {
        return $this->Title;
    }
    public function getType()
    {
        return $this->EvenementType;
    }
    public function getEvenementType()
    {
        return $this->EvenementType;
    } // Alias for backward compat
    public function getNote()
    {
        return $this->note;
    }
    public function getDocument()
    {
        return $this->Document;
    }

    public function setId($id)
    {
        $this->id = $id;
    }
    public function setDoctor($Doctor)
    {
        $this->Doctor = $Doctor;
    }
    public function setDate($Date)
    {
        $this->Date = $Date;
    }
    public function setTitle($Title)
    {
        $this->Title = $Title;
    }
    public function setType($Type)
    {
        $this->EvenementType = $Type;
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

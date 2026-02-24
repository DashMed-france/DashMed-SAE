<?php

declare(strict_types=1);

namespace modules\models\entities;

use modules\models\interfaces\EntityInterface;

/**
 * Class Patient
 *
 * Represents a patient in the system.
 *
 * @package DashMed\Modules\Models\Entities
 * @author DashMed Team
 * @license Proprietary
 */
class Patient implements EntityInterface
{
    private int $id;
    private string $firstName;
    private string $lastName;
    private ?string $birthDate;
    private ?string $gender;
    private ?string $admissionCause;
    private string $medicalHistory;

    public function __construct(
        int $id,
        string $firstName,
        string $lastName,
        ?string $birthDate = null,
        ?string $gender = null,
        ?string $admissionCause = null,
        string $medicalHistory = ''
    ) {
        $this->id = $id;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->birthDate = $birthDate;
        $this->gender = $gender;
        $this->admissionCause = $admissionCause;
        $this->medicalHistory = $medicalHistory;
    }

    public function getId(): int
    {
        return $this->id;
    }
    public function getFirstName(): string
    {
        return $this->firstName;
    }
    public function getLastName(): string
    {
        return $this->lastName;
    }
    public function getBirthDate(): ?string
    {
        return $this->birthDate;
    }
    public function getGender(): ?string
    {
        return $this->gender;
    }
    public function getAdmissionCause(): ?string
    {
        return $this->admissionCause;
    }
    public function getMedicalHistory(): string
    {
        return $this->medicalHistory;
    }
    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return [
            'id_patient' => $this->id,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'birth_date' => $this->birthDate,
            'gender' => $this->gender,
            'admission_cause' => $this->admissionCause,
            'medical_history' => $this->medicalHistory,
        ];
    }
}

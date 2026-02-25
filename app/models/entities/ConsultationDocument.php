<?php

declare(strict_types=1);

namespace modules\models\entities;

/**
 * Class ConsultationDocument
 *
 * Represents a PDF document attached to a medical consultation.
 *
 * @package DashMed\Modules\Models\Entities
 * @author DashMed Team
 * @license Proprietary
 */
class ConsultationDocument
{
    /** @var int Document ID */
    private int $id;

    /** @var int Parent consultation ID */
    private int $idConsultation;

    /** @var string Display filename (original name) */
    private string $filename;

    /** @var string Stored filename on disk (UUID.pdf) */
    private string $storedFilename;

    /** @var string MIME type */
    private string $mimeType;

    /** @var int File size in bytes */
    private int $fileSize;

    /** @var int Uploader user ID */
    private int $uploadedBy;

    /** @var string Upload datetime */
    private string $createdAt;

    /**
     * Constructor
     *
     * @param int    $id             Document ID
     * @param int    $idConsultation Parent consultation ID
     * @param string $filename       Display filename
     * @param string $storedFilename Stored filename on disk
     * @param string $mimeType       MIME type
     * @param int    $fileSize       File size in bytes
     * @param int    $uploadedBy     Uploader user ID
     * @param string $createdAt      Upload datetime
     */
    public function __construct(
        int    $id,
        int    $idConsultation,
        string $filename,
        string $storedFilename,
        string $mimeType,
        int    $fileSize,
        int    $uploadedBy,
        string $createdAt = ''
    ) {
        $this->id             = $id;
        $this->idConsultation = $idConsultation;
        $this->filename       = $filename;
        $this->storedFilename = $storedFilename;
        $this->mimeType       = $mimeType;
        $this->fileSize       = $fileSize;
        $this->uploadedBy     = $uploadedBy;
        $this->createdAt      = $createdAt;
    }

    /** @return int */
    public function getId(): int
    {
        return $this->id;
    }

    /** @return int */
    public function getIdConsultation(): int
    {
        return $this->idConsultation;
    }

    /** @return string */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /** @return string */
    public function getStoredFilename(): string
    {
        return $this->storedFilename;
    }

    /** @return string */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /** @return int */
    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    /** @return int */
    public function getUploadedBy(): int
    {
        return $this->uploadedBy;
    }

    /** @return string */
    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    /**
     * Returns a human-readable file size.
     *
     * @return string e.g. "1.2 Mo"
     */
    public function getHumanSize(): string
    {
        if ($this->fileSize >= 1_048_576) {
            return round($this->fileSize / 1_048_576, 1) . ' Mo';
        }
        if ($this->fileSize >= 1024) {
            return round($this->fileSize / 1024, 0) . ' Ko';
        }
        return $this->fileSize . ' o';
    }

    /**
     * @return array{id: int, id_consultation: int, filename: string, stored_filename: string,
     *               mime_type: string, file_size: int, uploaded_by: int, created_at: string}
     */
    public function toArray(): array
    {
        return [
            'id'              => $this->id,
            'id_consultation' => $this->idConsultation,
            'filename'        => $this->filename,
            'stored_filename' => $this->storedFilename,
            'mime_type'       => $this->mimeType,
            'file_size'       => $this->fileSize,
            'uploaded_by'     => $this->uploadedBy,
            'created_at'      => $this->createdAt,
        ];
    }
}

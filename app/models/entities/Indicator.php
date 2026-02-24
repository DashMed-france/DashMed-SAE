<?php

declare(strict_types=1);

namespace modules\models\entities;

use modules\models\interfaces\EntityInterface;

/**
 * Class Indicator
 *
 * Represents a health indicator (metric) for a patient.
 *
 * @package DashMed\Modules\Models\Entities
 * @author DashMed Team
 * @license Proprietary
 */
class Indicator implements EntityInterface
{
    private string $parameterId;
    private ?float $value;
    private ?string $timestamp;
    private int $alertFlag;

    private string $displayName;
    private string $category;
    private string $unit;
    private ?string $description;

    private ?float $normalMin;
    private ?float $normalMax;
    private ?float $criticalMin;
    private ?float $criticalMax;
    private ?float $displayMin;
    private ?float $displayMax;

    private string $defaultChart;
    /** @var array<int, string> */
    private array $allowedCharts;

    private string $status;

    /** @var array<string, mixed> Data for view matching (preferences, order, etc.) */
    private array $viewData = [];

    /** @var array<int, array<string, mixed>> History of values */
    private array $history = [];

    /** @var int Priority level for display */
    private int $priority = 0;

    /** @var int Display order */
    private int $displayOrder = 9999;

    /** @var bool Force show flag */
    private bool $forceShown = false;

    /** @var string Selected chart type */
    private string $chartType = 'line';

    /**
     * @param array<int, string> $allowedCharts
     */
    public function __construct(
        string $parameterId,
        ?float $value,
        ?string $timestamp,
        int $alertFlag,
        string $displayName,
        string $category,
        string $unit,
        ?string $description,
        ?float $normalMin,
        ?float $normalMax,
        ?float $criticalMin,
        ?float $criticalMax,
        ?float $displayMin,
        ?float $displayMax,
        string $defaultChart,
        array $allowedCharts,
        string $status
    ) {
        $this->parameterId = $parameterId;
        $this->value = $value;
        $this->timestamp = $timestamp;
        $this->alertFlag = $alertFlag;
        $this->displayName = $displayName;
        $this->category = $category;
        $this->unit = $unit;
        $this->description = $description;
        $this->normalMin = $normalMin;
        $this->normalMax = $normalMax;
        $this->criticalMin = $criticalMin;
        $this->criticalMax = $criticalMax;
        $this->displayMin = $displayMin;
        $this->displayMax = $displayMax;
        $this->defaultChart = $defaultChart;
        $this->allowedCharts = $allowedCharts;
        $this->status = $status;
    }

    public function getId(): string
    {
        return $this->parameterId;
    }

    public function getValue(): ?float
    {
        return $this->value;
    }

    public function setValue(?float $value): void
    {
        $this->value = $value;
    }

    public function getTimestamp(): ?string
    {
        return $this->timestamp;
    }

    public function setTimestamp(?string $timestamp): void
    {
        $this->timestamp = $timestamp;
    }

    public function getAlertFlag(): int
    {
        return $this->alertFlag;
    }

    public function setAlertFlag(int $flag): void
    {
        $this->alertFlag = $flag;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getNormalMin(): ?float
    {
        return $this->normalMin;
    }

    public function getNormalMax(): ?float
    {
        return $this->normalMax;
    }

    public function getCriticalMin(): ?float
    {
        return $this->criticalMin;
    }

    public function getCriticalMax(): ?float
    {
        return $this->criticalMax;
    }

    public function getDisplayMin(): ?float
    {
        return $this->displayMin;
    }

    public function getDisplayMax(): ?float
    {
        return $this->displayMax;
    }

    public function getDefaultChart(): string
    {
        return $this->defaultChart;
    }

    /**
     * @return array<int, string>
     */
    public function getAllowedCharts(): array
    {
        return $this->allowedCharts;
    }

    /**
     * @param array<int, string> $charts
     */
    public function setAllowedCharts(array $charts): void
    {
        $this->allowedCharts = $charts;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function setViewData(array $data): void
    {
        $this->viewData = $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function getViewData(): array
    {
        return $this->viewData;
    }

    /**
     * @param array<int, array<string, mixed>> $history
     */
    public function setHistory(array $history): void
    {
        $this->history = $history;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getHistory(): array
    {
        return $this->history;
    }

    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setDisplayOrder(int $order): void
    {
        $this->displayOrder = $order;
    }

    public function getDisplayOrder(): int
    {
        return $this->displayOrder;
    }

    public function setForceShown(bool $shown): void
    {
        $this->forceShown = $shown;
    }

    public function isForceShown(): bool
    {
        return $this->forceShown;
    }

    public function setChartType(string $type): void
    {
        $this->chartType = $type;
    }

    public function getChartType(): string
    {
        return $this->chartType;
    }

    public function toArray(): array
    {
        return [
            'parameter_id' => $this->parameterId,
            'value' => $this->value,
            'timestamp' => $this->timestamp,
            'alert_flag' => $this->alertFlag,
            'display_name' => $this->displayName,
            'category' => $this->category,
            'unit' => $this->unit,
            'description' => $this->description,
            'normal_min' => $this->normalMin,
            'normal_max' => $this->normalMax,
            'critical_min' => $this->criticalMin,
            'critical_max' => $this->criticalMax,
            'display_min' => $this->displayMin,
            'display_max' => $this->displayMax,
            'default_chart' => $this->defaultChart,
            'allowed_charts' => $this->allowedCharts,
            'status' => $this->status,
            'priority' => $this->priority,
            'display_order' => $this->displayOrder,
            'force_shown' => $this->forceShown,
            'chart_type' => $this->chartType,
            'view_data' => $this->viewData,
            'history' => $this->history
        ];
    }
}

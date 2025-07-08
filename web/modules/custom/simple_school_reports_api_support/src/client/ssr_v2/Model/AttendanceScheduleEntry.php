<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class AttendanceScheduleEntry extends \ArrayObject
{
    /**
     * @var array
     */
    protected $initialized = [];
    public function isInitialized($property): bool
    {
        return array_key_exists($property, $this->initialized);
    }
    /**
     * Anger vecka för alternerande schema, exempelvis 0, 1, eller 2 beroende på hur många olika veckor som är specificerade i schemat.
     *
     * @var int
     */
    protected $weekOffset;
    /**
     * Anger veckodag.
     *
     * @var string
     */
    protected $dayOfWeek;
    /**
     * Starttid på dagen för schemat (IS08601 format, t.ex. "08:30").
     *
     * @var string
     */
    protected $startTime;
    /**
     * Sluttid på dagen för schemat (IS08601 format, t.ex. "15:30").
     *
     * @var int
     */
    protected $endTime;
    /**
     * Anger vecka för alternerande schema, exempelvis 0, 1, eller 2 beroende på hur många olika veckor som är specificerade i schemat.
     *
     * @return int
     */
    public function getWeekOffset(): int
    {
        return $this->weekOffset;
    }
    /**
     * Anger vecka för alternerande schema, exempelvis 0, 1, eller 2 beroende på hur många olika veckor som är specificerade i schemat.
     *
     * @param int $weekOffset
     *
     * @return self
     */
    public function setWeekOffset(int $weekOffset): self
    {
        $this->initialized['weekOffset'] = true;
        $this->weekOffset = $weekOffset;
        return $this;
    }
    /**
     * Anger veckodag.
     *
     * @return string
     */
    public function getDayOfWeek(): string
    {
        return $this->dayOfWeek;
    }
    /**
     * Anger veckodag.
     *
     * @param string $dayOfWeek
     *
     * @return self
     */
    public function setDayOfWeek(string $dayOfWeek): self
    {
        $this->initialized['dayOfWeek'] = true;
        $this->dayOfWeek = $dayOfWeek;
        return $this;
    }
    /**
     * Starttid på dagen för schemat (IS08601 format, t.ex. "08:30").
     *
     * @return string
     */
    public function getStartTime(): string
    {
        return $this->startTime;
    }
    /**
     * Starttid på dagen för schemat (IS08601 format, t.ex. "08:30").
     *
     * @param string $startTime
     *
     * @return self
     */
    public function setStartTime(string $startTime): self
    {
        $this->initialized['startTime'] = true;
        $this->startTime = $startTime;
        return $this;
    }
    /**
     * Sluttid på dagen för schemat (IS08601 format, t.ex. "15:30").
     *
     * @return int
     */
    public function getEndTime(): int
    {
        return $this->endTime;
    }
    /**
     * Sluttid på dagen för schemat (IS08601 format, t.ex. "15:30").
     *
     * @param int $endTime
     *
     * @return self
     */
    public function setEndTime(int $endTime): self
    {
        $this->initialized['endTime'] = true;
        $this->endTime = $endTime;
        return $this;
    }
}
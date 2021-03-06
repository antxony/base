<?php

namespace App\Entity;

use App\Repository\SchedulePriorityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SchedulePriorityRepository::class)
 */
class SchedulePriority
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="string", length=15)
     */
    private $color;

    /**
     * @ORM\OneToMany(targetEntity=Schedule::class, mappedBy="priority")
     */
    private $schedules;

    /**
     * @ORM\OneToMany(targetEntity=ScheduleRecurrent::class, mappedBy="priority")
     */
    private $schedulesRecurrent;

    public function __construct()
    {
        $this->schedules = new ArrayCollection();
        $this->schedulesRecurrent = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;

        return $this;
    }

    /**
     * @return Collection|Schedule[]
     */
    public function getSchedules(bool $full = false): Collection
    {
        if($full) {
            return $this->schedules;
        } else {
            $criteria = new Criteria();
            $criteria->where(Criteria::expr()->eq('done', '0'));
            return $this->schedules->matching($criteria);
        }
    }

    public function addSchedule(Schedule $schedule): self
    {
        if (!$this->schedules->contains($schedule)) {
            $this->schedules[] = $schedule;
            $schedule->setPriority($this);
        }

        return $this;
    }

    public function removeSchedule(Schedule $schedule): self
    {
        if ($this->schedules->removeElement($schedule)) {
            // set the owning side to null (unless already changed)
            if ($schedule->getPriority() === $this) {
                $schedule->setPriority(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|ScheduleRecurrent[]
     */
    public function getSchedulesRecurrent(bool $full = false): Collection
    {
        if($full) {
            return $this->schedulesRecurrent;
        } else {
            $criteria = new Criteria();
            $criteria->where(Criteria::expr()->eq('done', '0'));
            return $this->schedulesRecurrent->matching($criteria);
        }
    }

    public function addScheduleRecurrent(ScheduleRecurrent $scheduleRecurrent): self
    {
        if (!$this->schedulesRecurrent->contains($scheduleRecurrent)) {
            $this->schedulesRecurrent[] = $scheduleRecurrent;
            $scheduleRecurrent->setPriority($this);
        }

        return $this;
    }

    public function removeScheduleRecurrent(ScheduleRecurrent $scheduleRecurrent): self
    {
        if ($this->schedulesRecurrent->removeElement($scheduleRecurrent)) {
            // set the owning side to null (unless already changed)
            if ($scheduleRecurrent->getPriority() === $this) {
                $scheduleRecurrent->setPriority(null);
            }
        }

        return $this;
    }
}

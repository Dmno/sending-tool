<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CampaignRepository")
 */
class Campaign
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\CampaignContent")
     */
    private $campaignContent;

    /**
     * @ORM\Column(type="integer")
     */
    private $speed;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Task", mappedBy="campaign")
     */
    private $tasks;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Batch", inversedBy="campaigns")
     * @ORM\JoinColumn(nullable=false)
     */
    private $batch;

    public function __construct()
    {
        $this->tasks = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCampaignContent(): ?CampaignContent
    {
        return $this->campaignContent;
    }

    public function setCampaignContent(?CampaignContent $campaignContent): self
    {
        $this->campaignContent = $campaignContent;

        return $this;
    }

    public function getSpeed(): ?int
    {
        return $this->speed;
    }

    public function setSpeed(int $speed): self
    {
        $this->speed = $speed;

        return $this;
    }

    /**
     * @return Collection|Task[]
     */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(Task $task): self
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks[] = $task;
            $task->setCampaign($this);
        }

        return $this;
    }

    public function removeTask(Task $task): self
    {
        if ($this->tasks->contains($task)) {
            $this->tasks->removeElement($task);
            // set the owning side to null (unless already changed)
            if ($task->getCampaign() === $this) {
                $task->setCampaign(null);
            }
        }

        return $this;
    }

    public function getBatch(): ?Batch
    {
        return $this->batch;
    }

    public function setBatch(?Batch $batch): self
    {
        $this->batch = $batch;

        return $this;
    }
}

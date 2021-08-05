<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ServerRepository")
 */
class Server
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $ip;

    /**
     * @ORM\Column(type="integer")
     */
    private $retry = 0;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $retryAt;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Batch", inversedBy="servers")
     * @ORM\JoinColumn(nullable=false)
     */
    private $batch;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Task", mappedBy="server", cascade={"persist", "remove"})
     */
    private $tasks;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Task", cascade={"persist", "remove"})
     */
    private $currentTask;

    /**
     * @ORM\Column(type="boolean")
     */
    private $dead = false;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @ORM\Column(type="boolean")
     */
    private $dnsCheck = false;

    public function __construct($ip, $batch)
    {
        $this->ip = $ip;
        $this->batch = $batch;
        $this->tasks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    public function getRetry(): ?int
    {
        return $this->retry;
    }

    public function setRetry(int $retry): self
    {
        $this->retry = $retry;

        return $this;
    }

    public function getRetryAt(): ?\DateTimeInterface
    {
        return $this->retryAt;
    }

    public function setRetryAt(\DateTimeInterface $retryAt): self
    {
        $this->retryAt = $retryAt;

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
            $task->setServer($this);
        }

        return $this;
    }

    public function removeTask(Task $task): self
    {
        if ($this->tasks->contains($task)) {
            $this->tasks->removeElement($task);
            // set the owning side to null (unless already changed)
            if ($task->getServer() === $this) {
                $task->setServer(null);
            }
        }

        return $this;
    }

    public function getCurrentTask(): ?Task
    {
        return $this->currentTask;
    }

    public function setCurrentTask(?Task $currentTask): self
    {
        $this->currentTask = $currentTask;

        return $this;
    }

    public function getDead()
    {
        return $this->dead;
    }

    public function setDead($dead): void
    {
        $this->dead = $dead;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getDnsCheck(): ?bool
    {
        return $this->dnsCheck;
    }

    public function setDnsCheck(?bool $dnsCheck): self
    {
        $this->dnsCheck = $dnsCheck;

        return $this;
    }
}

<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TaskRepository")
 */
class Task
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Server", inversedBy="tasks", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $server;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Campaign", inversedBy="tasks")
     * @ORM\JoinColumn(nullable=false)
     */
    private $campaign;

    /**
     * ["waiting", "sending", "sent"]
     *
     * @ORM\Column(type="string", length=255)
     */
    private $status = "waiting";

    /**
     * @ORM\Column(type="integer")
     */
    private $progress = 0;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Import", inversedBy="task")
     * @ORM\JoinColumn(nullable=false)
     */
    private $import;

    /**
     * @ORM\Column(type="integer")
     */
    private $sent = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $opens = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $bounces = 0;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $campaignUid;

    /**
     * @ORM\Column(type="boolean")
     */
    private $resent = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getServer(): ?Server
    {
        return $this->server;
    }

    public function setServer(?Server $server): self
    {
        $this->server = $server;

        return $this;
    }

    public function getCampaign(): ?Campaign
    {
        return $this->campaign;
    }

    public function setCampaign($campaign): void
    {
        $this->campaign = $campaign;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getProgress(): ?int
    {
        return $this->progress;
    }

    public function setProgress(int $progress): self
    {
        $this->progress = $progress;

        return $this;
    }

    public function getImport(): ?Import
    {
        return $this->import;
    }

    public function setImport(?Import $import): self
    {
        $this->import = $import;

        return $this;
    }

    public function getSent()
    {
        return $this->sent;
    }

    public function setSent($sent): void
    {
        $this->sent = $sent;
    }

    public function getOpens()
    {
        return $this->opens;
    }

    public function setOpens($opens): void
    {
        $this->opens = $opens;
    }

    public function getBounces()
    {
        return $this->bounces;
    }

    public function setBounces($bounces): void
    {
        $this->bounces = $bounces;
    }

    public function getCampaignUid()
    {
        return $this->campaignUid;
    }

    public function setCampaignUid($campaignUid): void
    {
        $this->campaignUid = $campaignUid;
    }

    public function getResent(): ?bool
    {
        return $this->resent;
    }

    public function setResent(bool $resent): self
    {
        $this->resent = $resent;

        return $this;
    }
}

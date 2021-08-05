<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @ORM\Entity(repositoryClass="App\Repository\BatchRepository")
 */
class Batch
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
    private $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="batches")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Server", mappedBy="batch")
     */
    private $servers;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="boolean")
     */
    private $visible = true;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Campaign", mappedBy="batch")
     */
    private $campaigns;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ContactList")
     * @ORM\JoinColumn(nullable=false)
     */
    private $contactList;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $mode;

    /**
     * @ORM\Column(type="boolean")
     */
    private $setup = false;

    /**
     * @ORM\Column(type="float")
     */
    private $cost = 0;

    /**
     * @ORM\Column(type="float")
     */
    private $revenue = 0;

    public static $modeChoices = [
        "Warmup plan" => "warmup",
        "Send linearly" => "linear",
        "Send simultaneously" => "simultaneous"
    ];

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $category;

    public function __construct()
    {
        $this->servers = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->campaigns = new ArrayCollection();
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection|Server[]
     */
    public function getServers(): Collection
    {
        return $this->servers;
    }

    public function addServer(Server $server): self
    {
        if (!$this->servers->contains($server)) {
            $this->servers[] = $server;
            $server->setBatch($this);
        }

        return $this;
    }

    public function removeServer(Server $server): self
    {
        if ($this->servers->contains($server)) {
            $this->servers->removeElement($server);
            // set the owning side to null (unless already changed)
            if ($server->getBatch() === $this) {
                $server->setBatch(null);
            }
        }

        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt($createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getVisible(): ?bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): self
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * @return Collection|Campaign[]
     */
    public function getCampaigns(): Collection
    {
        return $this->campaigns;
    }

    public function addCampaign(Campaign $campaign): self
    {
        if (!$this->campaigns->contains($campaign)) {
            $this->campaigns[] = $campaign;
            $campaign->setBatch($this);
        }

        return $this;
    }

    public function removeCampaign(Campaign $campaign): self
    {
        if ($this->campaigns->contains($campaign)) {
            $this->campaigns->removeElement($campaign);
            // set the owning side to null (unless already changed)
            if ($campaign->getBatch() === $this) {
                $campaign->setBatch(null);
            }
        }

        return $this;
    }

    public function getContactList(): ?ContactList
    {
        return $this->contactList;
    }

    public function setContactList(?ContactList $contactList): self
    {
        $this->contactList = $contactList;

        return $this;
    }

    public function getMode(): ?string
    {
        return $this->mode;
    }

    public function setMode(string $mode): self
    {
        $this->mode = $mode;

        return $this;
    }

    public function getSetup()
    {
        return $this->setup;
    }

    public function setSetup($setup): void
    {
        $this->setup = $setup;
    }

    public function getCost()
    {
        return $this->cost;
    }

    public function setCost($cost): void
    {
        $this->cost = $cost;
    }

    public function getRevenue()
    {
        return $this->revenue;
    }

    public function setRevenue($revenue): void
    {
        $this->revenue = $revenue;
    }

    public function getAliveCount()
    {
        $count = 0;
        foreach ($this->getServers() as $server) {
            if (!$server->getDead()) {
                $count++;
            }
        }
        return $count;
    }

    public function getDeadCount()
    {
        $count = 0;
        foreach ($this->getServers() as $server) {
            if ($server->getDead()) {
                $count++;
            }
        }
        return $count;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category;

        return $this;
    }
}

<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * User
 *
 * @ORM\Table(name="images")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ImageRepository")
 * @ORM\HasLifecycleCallbacks
 * @ExclusionPolicy("all")
 */
class Image
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User", inversedBy="images")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $user;

    /**
     * @ORM\Column(type="string")
     *
     * @Assert\NotBlank(message="Please, upload the image.", groups={"upload"})
     * @Assert\Image(maxSize="2M", groups={"upload"})
     * @Groups({"upload"})
     * @Expose()
     */
    private $image;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $hashtag;

    /**
     * @ORM\Column(type="float")
     * @Assert\NotBlank(groups={"upload"})
     */
    private $latitude;

    /**
     * @ORM\Column(type="float")
     * @Assert\NotBlank(groups={"upload"})
     */
    private $longitude;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $smallImagePath;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $bigImagePath;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $weather;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $address;

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"login"})
     * @Expose()
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private $modifiedAt;

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updatedTimestamps()
    {
        $this->setModifiedAt(new \DateTime('now'));

        if ($this->getCreatedAt() == null) {
            $this->setCreatedAt(new \DateTime('now'));
        }
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set image
     *
     * @param string $image
     *
     * @return Image
     */
    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get image
     *
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return Image
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set hashtag
     *
     * @param string $hashtag
     *
     * @return Image
     */
    public function setHashtag($hashtag)
    {
        $this->hashtag = $hashtag;

        return $this;
    }

    /**
     * Get hashtag
     *
     * @return string
     */
    public function getHashtag()
    {
        return $this->hashtag;
    }

    /**
     * Set latitude
     *
     * @param float $latitude
     *
     * @return Image
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * Get latitude
     *
     * @return float
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Set longitude
     *
     * @param float $longitude
     *
     * @return Image
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * Get longitude
     *
     * @return float
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Set smallImagePath
     *
     * @param string $smallImagePath
     *
     * @return Image
     */
    public function setSmallImagePath($smallImagePath)
    {
        $this->smallImagePath = $smallImagePath;

        return $this;
    }

    /**
     * Get smallImagePath
     *
     * @return string
     */
    public function getSmallImagePath()
    {
        return $this->smallImagePath;
    }

    /**
     * Set bigImagePath
     *
     * @param string $bigImagePath
     *
     * @return Image
     */
    public function setBigImagePath($bigImagePath)
    {
        $this->bigImagePath = $bigImagePath;

        return $this;
    }

    /**
     * Get bigImagePath
     *
     * @return string
     */
    public function getBigImagePath()
    {
        return $this->bigImagePath;
    }

    /**
     * Set weather
     *
     * @param string $weather
     *
     * @return Image
     */
    public function setWeather($weather)
    {
        $this->weather = $weather;

        return $this;
    }

    /**
     * Get weather
     *
     * @return string
     */
    public function getWeather()
    {
        return $this->weather;
    }

    /**
     * Set address
     *
     * @param string $address
     *
     * @return Image
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Image
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set modifiedAt
     *
     * @param \DateTime $modifiedAt
     *
     * @return Image
     */
    public function setModifiedAt($modifiedAt)
    {
        $this->modifiedAt = $modifiedAt;

        return $this;
    }

    /**
     * Get modifiedAt
     *
     * @return \DateTime
     */
    public function getModifiedAt()
    {
        return $this->modifiedAt;
    }

    /**
     * Set user
     *
     * @param \AppBundle\Entity\User $user
     *
     * @return Image
     */
    public function setUser(\AppBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \AppBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }
}

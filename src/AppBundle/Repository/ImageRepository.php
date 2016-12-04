<?php

namespace AppBundle\Repository;

use AppBundle\Entity\User;
use Doctrine\ORM\EntityRepository;

/**
 * Class ImageRepository
 * @package AppBundle\Repository
 */
class ImageRepository extends EntityRepository
{
    /**
     * @param User $user
     * @param null|string $weather
     * @param int $count
     *
     * @return array
     */
    public function findImageByWeather(User $user, $weather = null, $count = 5)
    {
        $qb = $this->createQueryBuilder('im');
        $qb->where('im.user = :user')
            ->setMaxResults($count)
            ->setParameter('user', $user);

        if ($weather) {
            $qb
                ->andWhere('im.weather = :weather')
                ->setParameter('weather', $weather);
        }

        return $qb->getQuery()->getResult();
    }
}

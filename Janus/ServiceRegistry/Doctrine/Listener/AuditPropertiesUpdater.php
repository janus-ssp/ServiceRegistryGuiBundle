<?php
namespace Janus\ServiceRegistry\Doctrine\Listener;

use Exception;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;

use DateTime;

use Janus\ServiceRegistry\DependencyInjection\AuthProviderInterface;
use Janus\ServiceRegistry\Entity\User;
use Janus\ServiceRegistry\Value\Ip;

class AuditPropertiesUpdater
{
    const DEFAULT_IP = '127.0.0.1';

    /**
     * @var AuthProviderInterface
     */
    private $auth;

    public function __construct(AuthProviderInterface $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Executes on every flush. All entities that are scheduled for persistence can be changed here.
     * @param \Doctrine\ORM\Event\OnFlushEventArgs $eventArgs
     */
    public function onFlush(\Doctrine\ORM\Event\OnFlushEventArgs $eventArgs)
    {
        $em = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();

        if (isset($_SERVER['REMOTE_ADDR'])) {
            $userIp = new Ip($_SERVER['REMOTE_ADDR']);
        } else {
            $userIp = new Ip(self::DEFAULT_IP);
        }
        $auth = $this->auth;
        $loggedInUser = function () use ($auth, $em) {
            return $this->getLoggedInUser($em, $auth);
        };
        $methods = array(
            'setCreatedAtDate' => array(
                'insertValue' => new DateTime(),
            ),
            'setUpdatedAtDate' => array(
                'updateValue' => new DateTime(),
            ),
            // @todo fix that deleted date accepts null values
            'setUpdatedByUser' => array(
                'insertValue' => $loggedInUser,
                'updateValue' => $loggedInUser
            ),
            'setUpdatedFromIp' => array(
                'insertValue' => $userIp,
                'updateValue' => $userIp
            )
        );

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            $class = get_class($entity);
            foreach ($methods as $method => $values) {
                if (isset($values['insertValue']) && method_exists($entity, $method)) {
                    $value = is_callable($values['insertValue']) ? $values['insertValue']() : $values['insertValue'];
                    $entity->$method($value);
                }
            }

            // needed to save the changed date value
            $uow->recomputeSingleEntityChangeSet($em->getClassMetadata($class), $entity);
            $em->persist($entity);
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $class = get_class($entity);
            foreach ($methods as $method => $values) {
                if (isset($values['updateValue']) && method_exists($entity, $method)) {
                    $value = is_callable($values['updateValue']) ? $values['updateValue']() : $values['updateValue'];
                    $entity->$method($value);
                }
            }

            // needed to save the changed date value
            $uow->recomputeSingleEntityChangeSet($em->getClassMetadata($class), $entity);
            $em->persist($entity);
        }
    }

    /**
     * @param EntityManager $entityManager
     * @param AuthProviderInterface $auth
     * @return User
     * @throws \Exception
     */
    private function getLoggedInUser(EntityManager $entityManager, AuthProviderInterface $auth)
    {
        $username = $auth->getLoggedInUsername();
        $user = $entityManager->getRepository('Janus\ServiceRegistry\Entity\User')
            ->findOneBy(array('username' => $username));

        if (!$user instanceof User) {
            throw new Exception("No User logged in");
        }

        return $user;
    }
}

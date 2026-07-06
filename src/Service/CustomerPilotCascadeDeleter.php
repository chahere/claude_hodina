<?php

namespace App\Service;

use App\Entity\Address;
use App\Entity\Customer;
use App\Entity\CustomerOrder;
use App\Entity\SmsLog;
use Doctrine\ORM\EntityManagerInterface;

final class CustomerPilotCascadeDeleter
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array{orders:int, smsLogs:int, addresses:int}
     */
    public function preview(Customer $customer): array
    {
        $orders = $this->findOrders($customer);

        return [
            'orders' => count($orders),
            'smsLogs' => $this->countSmsLogs($orders),
            'addresses' => count($customer->getAddresses()),
        ];
    }

    /**
     * Suppression physique réservée à la phase pilote.
     *
     * @return array{orders:int, smsLogs:int, addresses:int}
     */
    public function delete(Customer $customer): array
    {
        $orders = $this->findOrders($customer);
        $summary = [
            'orders' => count($orders),
            'smsLogs' => $this->countSmsLogs($orders),
            'addresses' => count($customer->getAddresses()),
        ];

        $this->entityManager->beginTransaction();

        try {
            $this->deleteSmsLogsForOrders($orders);
            $this->deleteOrders($orders);

            if ($customer->getBillingAddress() instanceof Address) {
                $customer->setBillingAddress(null);
            }

            foreach ($customer->getAddresses()->toArray() as $address) {
                if ($address instanceof Address) {
                    $this->entityManager->remove($address);
                }
            }

            $this->entityManager->remove($customer);
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Throwable $exception) {
            $this->entityManager->rollback();
            throw $exception;
        }

        return $summary;
    }

    /**
     * @return CustomerOrder[]
     */
    private function findOrders(Customer $customer): array
    {
        return $this->entityManager
            ->getRepository(CustomerOrder::class)
            ->findBy(['customer' => $customer]);
    }

    /**
     * @param CustomerOrder[] $orders
     */
    private function countSmsLogs(array $orders): int
    {
        if ($orders === []) {
            return 0;
        }

        return (int) $this->entityManager
            ->createQueryBuilder()
            ->select('COUNT(s.id)')
            ->from(SmsLog::class, 's')
            ->where('s.customerOrder IN (:orders)')
            ->setParameter('orders', $orders)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param CustomerOrder[] $orders
     */
    private function deleteSmsLogsForOrders(array $orders): void
    {
        if ($orders === []) {
            return;
        }

        $smsLogs = $this->entityManager
            ->createQueryBuilder()
            ->select('s')
            ->from(SmsLog::class, 's')
            ->where('s.customerOrder IN (:orders)')
            ->setParameter('orders', $orders)
            ->getQuery()
            ->getResult();

        foreach ($smsLogs as $smsLog) {
            $this->entityManager->remove($smsLog);
        }
    }

    /**
     * @param CustomerOrder[] $orders
     */
    private function deleteOrders(array $orders): void
    {
        foreach ($orders as $order) {
            $this->entityManager->remove($order);
        }
    }
}

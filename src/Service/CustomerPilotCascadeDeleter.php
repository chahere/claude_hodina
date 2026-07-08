<?php

namespace App\Service;

use App\Entity\Address;
use App\Entity\ChatbotConversation;
use App\Entity\Customer;
use App\Entity\CustomerOrder;
use App\Entity\CourierPayout;
use App\Entity\SmsLog;
use Doctrine\ORM\EntityManagerInterface;

final class CustomerPilotCascadeDeleter
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array{orders:int, smsLogs:int, addresses:int, chatbotConversations:int, courierPayouts:int}
     */
    public function preview(Customer $customer): array
    {
        $orders = $this->findOrders($customer);

        return [
            'orders' => count($orders),
            'smsLogs' => $this->countSmsLogs($orders),
            'addresses' => count($customer->getAddresses()),
            'chatbotConversations' => count($this->findConversations($customer)),
            'courierPayouts' => $this->countCourierPayouts($customer),
        ];
    }

    /**
     * Suppression physique réservée à la phase pilote.
     *
     * @return array{orders:int, smsLogs:int, addresses:int, chatbotConversations:int, courierPayouts:int}
     */
    public function delete(Customer $customer): array
    {
        $orders = $this->findOrders($customer);
        $conversations = $this->findConversations($customer);
        $summary = [
            'orders' => count($orders),
            'smsLogs' => $this->countSmsLogs($orders),
            'addresses' => count($customer->getAddresses()),
            'chatbotConversations' => count($conversations),
            // Les paiements livreur (courier_payout) sont en ON DELETE CASCADE en base :
            // supprimés automatiquement avec le client, comptés ici uniquement pour info.
            'courierPayouts' => $this->countCourierPayouts($customer),
        ];

        $this->entityManager->beginTransaction();

        try {
            $this->deleteSmsLogsForOrders($orders);
            $this->deleteOrders($orders);
            $this->deleteConversations($conversations);

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

    /**
     * @return ChatbotConversation[]
     */
    private function findConversations(Customer $customer): array
    {
        return $this->entityManager
            ->getRepository(ChatbotConversation::class)
            ->findBy(['customer' => $customer]);
    }

    /**
     * chatbot_message est en ON DELETE CASCADE depuis chatbot_conversation (migration
     * Version20260706120000) : supprimer la conversation suffit à supprimer ses messages
     * en base. support_ticket.chatbot_conversation_id est en ON DELETE SET NULL : un
     * ticket lié survit, il perd juste le lien vers la conversation supprimée.
     *
     * @param ChatbotConversation[] $conversations
     */
    private function deleteConversations(array $conversations): void
    {
        foreach ($conversations as $conversation) {
            $this->entityManager->remove($conversation);
        }
    }

    private function countCourierPayouts(Customer $customer): int
    {
        return (int) $this->entityManager
            ->createQueryBuilder()
            ->select('COUNT(p.id)')
            ->from(CourierPayout::class, 'p')
            ->where('p.courier = :customer')
            ->setParameter('customer', $customer)
            ->getQuery()
            ->getSingleScalarResult();
    }
}

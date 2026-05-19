<?php

namespace QUI\Users\Attribute\Verifiable;

use QUI;
use QUI\Exception;
use QUI\ExceptionStack;
use QUI\Users\Address;
use QUI\Users\Manager;

final class AddressAttribute extends AbstractVerifiableUserAttribute
{
    protected ?Address $Address = null;

    /**
     * @throws ExceptionStack
     * @throws Exception
     * @throws QUI\Users\Exception
     * @throws QUI\Database\Exception
     */
    public function getAddress(): Address
    {
        if ($this->Address) {
            return $this->Address;
        }

        $QueryBuilder = QUI::getQueryBuilder();
        $result = $QueryBuilder
            ->select("userUuid")
            ->from(Manager::tableAddress())
            ->where($QueryBuilder->expr()->eq("uuid", ":addressUuid"))
            ->setParameter("addressUuid", $this->value)
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        if (!$result) {
            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.lib.user.address.not.found',
                    [
                        'addressId' => $this->value
                    ]
                ),
                404
            );
        }

        $User = QUI::getUsers()->get($result["userUuid"]);
        $this->Address = new Address($User, $this->value);

        return $this->Address;
    }
}

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

        $result = QUI::getDataBase()->fetch([
            'from' => Manager::tableAddress(),
            'where' => [
                'uuid' => $this->value
            ],
            'limit' => 1
        ]);

        if (empty($result)) {
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

        $User = QUI::getUsers()->get($result[0]['userUuid']);
        $this->Address = new Address($User, $this->value);

        return $this->Address;
    }
}

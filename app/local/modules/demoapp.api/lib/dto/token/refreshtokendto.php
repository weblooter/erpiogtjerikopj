<?php

namespace NaturaSiberica\Api\DTO\Token;

use Bitrix\Main\Type\DateTime;
use NaturaSiberica\Api\Interfaces\Services\Token\TokenServiceInterface;
use Spatie\DataTransferObject\DataTransferObject;

class RefreshTokenDTO extends DataTransferObject
{
    /**
     * @var int|string
     */
    public $fuserId;

    public string $type = 'refresh';
    public string $token;
    public DateTime $created;
    public DateTime $expires;

    public static function create(array $fields)
    {
        return new static([
            'fuserId' => $fields['fuserId'],
            'token' => $fields['refreshToken'],
            'created' => $fields['created'],
            'expires' => $fields['expires']
        ]);
    }

    public function toArray(bool $returnTimestamps = true): array
    {
        $result = parent::toArray();

        if ($returnTimestamps) {
            if (!in_array('created', $this->exceptKeys)) {
                $result['created'] = $this->created->getTimestamp();
            }

            if (!in_array('expires', $this->exceptKeys)) {
                $result['expires'] = $this->expires->getTimestamp();
            }
        }

        return $result;
    }
}

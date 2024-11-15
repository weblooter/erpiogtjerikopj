<?php

namespace NaturaSiberica\Api\Services\Forms;

use \Bitrix\Main\Mail\Event;
use NaturaSiberica\Api\Traits\Entities\InfoBlockTrait;

class FormsService
{
    use InfoBlockTrait;

    private array $errors = [];

    public function sendFeedback(array $data, ?int $userId = null): array
    {
        $responseMessage = 'Ваше сообщение отправлено. Спасибо!';
        $requiredFields = ['name', 'email', 'feedbackTopic', 'message'];
        $requestFeedbackFields = [
            'name' => 'NAME',
            'email' => 'CODE',
            'message' => 'PREVIEW_TEXT'
        ];
        $requestFeedbackProps = [
            'feedbackTopic' => 'FEEDBACK_TOPIC'
        ];

        $list = ['ACTIVE' => 'Y','IBLOCK_SECTION_ID' => false];
        foreach ($data as $code => $item) {
            if(in_array($code, $requiredFields) && !$item) {
                $this->errors[$code] = sprintf('Поле "%s" не должно быть пустым', $code);
            }

            if($code === 'email' && !filter_var($item, FILTER_VALIDATE_EMAIL)) {
                if(!$this->errors[$code]) {
                    $this->errors[$code] = 'Не корректно введен email.';
                }
            }

            if($userId) {
                $list['CREATED_BY'] = $userId;
            }

            $list['IBLOCK_ID'] = $this->getIblockId('request_from_feedback');

            if($requestFeedbackFields[$code]) {
                $list[$requestFeedbackFields[$code]] = trim($item);
            }

            if($requestFeedbackProps[$code]) {
                $list['PROPERTY_VALUES'][$requestFeedbackProps[$code]] = trim($item);
            }
        }

        if($this->errors) {
            return ['id' => null, 'message' => null,'errors' => array_values($this->errors)];
        }

        return $this->addRequest($list, $responseMessage);
    }

    protected function addRequest(array $data, string $message = ''): array
    {
        $element = new \CIBlockElement;
        if($id = $element->Add($data)) {
        $this->sendEmail($data);
            return [
                'id' => $id,
                'message' => $message,
                'errors' => []
            ];
        }

        throw new \Exception($element->LAST_ERROR, 422);
    }

    private function sendEmail(array $data): bool
    {
        $result = Event::send([
            "EVENT_NAME" => "FEEDBACK_FORM",
            "LID"        => "s1",
            "C_FIELDS"   => [
                "AUTHOR_EMAIL" => $data['CODE'],
                "TOPIC"        => $data['PROPERTY_VALUES']['FEEDBACK_TOPIC'],
                "TEXT"         => $data['PREVIEW_TEXT'],
                "AUTHOR"       => $data['NAME'],
            ],
        ]);
        if ($result->isSuccess()) {
            return true;
        }
        return false;
    }
}

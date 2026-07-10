<?php
namespace TSEMOU\Modules\ParticipationEngine\Actions;

if (!defined('ABSPATH')) exit;

class Action_Result {
    private $success;
    private $status;
    private $message;
    private $data;
    private $errors;

    public function __construct($success, $status, $message = '', $data = [], $errors = []) {
        $this->success = (bool) $success;
        $this->status = sanitize_key((string) $status);
        $this->message = sanitize_text_field((string) $message);
        $this->data = is_array($data) ? $data : [];
        $this->errors = is_array($errors) ? $errors : [];
    }

    public static function success($status, $message = '', $data = []) {
        return new self(true, $status, $message, $data, []);
    }

    public static function failure($status, $message = '', $errors = []) {
        return new self(false, $status, $message, [], $errors);
    }

    public function is_success() {
        return $this->success;
    }

    public function to_array() {
        return [
            'success' => $this->success,
            'status' => $this->status,
            'message' => $this->message,
            'data' => $this->data,
            'errors' => $this->errors,
        ];
    }
}

<?php
namespace Utils;

class Validator {
    private $data;
    private $errors = [];

    public function __construct(array $data) {
        $this->data = $data;
    }

    public function validate(array $rules) {
     
        $this->errors = [];

        foreach ($rules as $field => $fieldRules) {
            $rulesArray = explode('|', $fieldRules);
            $value = $this->data[$field] ?? null;
            
            foreach ($rulesArray as $rule) {
                $ruleParts = explode(':', $rule, 2);
                $ruleName = $ruleParts[0];
                $ruleValue = $ruleParts[1] ?? null;

                switch ($ruleName) {
                    case 'nullable':
                        break;
                    case 'required':
                        if ($this->isEmpty($value)) {
                            $this->addError($field, "The {$field} field is required.");
                        }
                        break;
                    case 'requiredIf':
                        [$otherField, $expectedValue] = array_pad(explode(',', (string)$ruleValue, 2), 2, null);
                        $otherValue = $this->data[$otherField] ?? null;
                        if ($this->valuesMatch($otherValue, $expectedValue) && $this->isEmpty($value)) {
                            $this->addError($field, "The {$field} field is required when {$otherField} is {$expectedValue}.");
                        }
                        break;
                    case 'requiredWith':
                        $relatedFields = explode(',', (string)$ruleValue);
                        foreach ($relatedFields as $relatedField) {
                            if (!$this->isEmpty($this->data[$relatedField] ?? null) && $this->isEmpty($value)) {
                                $this->addError($field, "The {$field} field is required when {$relatedField} is present.");
                                break;
                            }
                        }
                        break;
                    case 'string':
                        if (!$this->isEmpty($value) && !is_string($value)) {
                            $this->addError($field, "The {$field} field must be text.");
                        }
                        break;
                    case 'numeric':
                        if (!$this->isEmpty($value) && (is_bool($value) || !is_numeric($value))) {
                            $this->addError($field, "The {$field} field must be a number.");
                        }
                        break;
                    case 'integer':
                        if (!$this->isEmpty($value)
                            && (is_bool($value) || filter_var($value, FILTER_VALIDATE_INT) === false)) {
                            $this->addError($field, "The {$field} field must be an integer.");
                        }
                        break;
                    case 'positiveInteger':
                        if (!$this->isEmpty($value)
                            && (is_bool($value)
                                || filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false)) {
                            $this->addError($field, "The {$field} field must be a positive integer.");
                        }
                        break;
                    case 'boolean':
                        if (!$this->isEmpty($value)
                            && !in_array($value, [true, false, 0, 1, '0', '1', 'true', 'false'], true)) {
                            $this->addError($field, "The {$field} field must be true or false.");
                        }
                        break;
                    case 'array':
                        if (!$this->isEmpty($value) && !is_array($value)) {
                            $this->addError($field, "The {$field} field must be an array.");
                        }
                        break;
                    case 'min':
                        if (!$this->isEmpty($value) && is_numeric($value) && (float)$value < (float)$ruleValue) {
                            $this->addError($field, "The {$field} field must be at least {$ruleValue}.");
                        }
                        break;
                    case 'max':
                        if (!$this->isEmpty($value) && is_numeric($value) && (float)$value > (float)$ruleValue) {
                            $this->addError($field, "The {$field} field must not be greater than {$ruleValue}.");
                        }
                        break;
                    case 'decimal':
                        if (!$this->isEmpty($value)) {
                            $decimalPlaces = max(0, (int)$ruleValue);
                            $pattern = $decimalPlaces === 0
                                ? '/^[0-9]+$/D'
                                : '/^[0-9]+(?:\.[0-9]{1,' . $decimalPlaces . '})?$/D';
                            if (is_bool($value) || (!is_string($value) && !is_int($value) && !is_float($value))
                                || !preg_match($pattern, (string)$value)) {
                                $this->addError($field, "The {$field} field must have no more than {$decimalPlaces} decimal places.");
                            }
                        }
                        break;
                    case 'maxLength':
                        if (!$this->isEmpty($value)) {
                            if (!is_string($value) && !is_numeric($value)) {
                                $this->addError($field, "The {$field} field must be text.");
                            } elseif ($this->textLength((string)$value) > (int)$ruleValue) {
                                $this->addError($field, "The {$field} field must not exceed {$ruleValue} characters.");
                            }
                        }
                        break;
                    case 'minLength':
                        if (!$this->isEmpty($value)) {
                            if (!is_string($value) && !is_numeric($value)) {
                                $this->addError($field, "The {$field} field must be text.");
                            } elseif ($this->textLength((string)$value) < (int)$ruleValue) {
                                $this->addError($field, "The {$field} field must be at least {$ruleValue} characters.");
                            }
                        }
                        break;
                    case 'email':
                        if (!$this->isEmpty($value)
                            && (!is_string($value) || !filter_var($value, FILTER_VALIDATE_EMAIL))) {
                            $this->addError($field, "The {$field} field must be a valid email address.");
                        }
                        break;
                    case 'phone':
                        if (!$this->isEmpty($value)
                            && (!is_string($value) || !preg_match('/^0[0-9]{9}$/D', $value))) {
                            $this->addError($field, "The {$field} field must be exactly 10 digits and start with 0.");
                        }
                        break;
                    case 'in':
                        if (!$this->isEmpty($value)) {
                            $allowedValues = explode(',', $ruleValue);
                            if (!is_scalar($value) || !in_array((string)$value, $allowedValues, true)) {
                                $this->addError($field, "The {$field} field must be one of: " . str_replace(',', ', ', $ruleValue) . ".");
                            }
                        }
                        break;
                    case 'same':
                        $otherValue = $this->data[(string)$ruleValue] ?? null;
                        if (!$this->isEmpty($value) && $value !== $otherValue) {
                            $this->addError($field, "The {$field} field must match {$ruleValue}.");
                        }
                        break;
                    case 'url':
                        if (!$this->isEmpty($value)) {
                            $scheme = is_string($value)
                                ? strtolower((string)parse_url($value, PHP_URL_SCHEME))
                                : '';
                            if (!is_string($value)
                                || filter_var($value, FILTER_VALIDATE_URL) === false
                                || !in_array($scheme, ['http', 'https'], true)) {
                                $this->addError($field, "The {$field} field must be a valid HTTP or HTTPS URL.");
                            }
                        }
                        break;
                    case 'date':
                        if (!$this->isEmpty($value)
                            && (!is_string($value) || strtotime($value) === false)) {
                            $this->addError($field, "The {$field} field must be a valid date.");
                        }
                        break;
                    case 'dateFormat':
                        if (!$this->isEmpty($value)
                            && (!is_string($value) || !$this->matchesDateFormat($value, (string)$ruleValue))) {
                            $this->addError($field, "The {$field} field must use the {$ruleValue} format.");
                        }
                        break;
                    case 'beforeOrEqual':
                        if (!$this->isEmpty($value) && is_string($value)) {
                            $timestamp = strtotime($value);
                            $limit = $ruleValue === 'now' ? time() : strtotime((string)$ruleValue);
                            if ($timestamp !== false && $limit !== false && $timestamp > $limit) {
                                $this->addError($field, "The {$field} field must be before or equal to {$ruleValue}.");
                            }
                        }
                        break;
                    case 'afterOrEqual':
                        if (!$this->isEmpty($value) && is_string($value)) {
                            $timestamp = strtotime($value);
                            $limit = $ruleValue === 'now' ? time() : strtotime((string)$ruleValue);
                            if ($timestamp !== false && $limit !== false && $timestamp < $limit) {
                                $this->addError($field, "The {$field} field must be after or equal to {$ruleValue}.");
                            }
                        }
                        break;
                    case 'regex':
                        if (!$this->isEmpty($value)
                            && (!is_string($value) || @preg_match((string)$ruleValue, $value) !== 1)) {
                            $this->addError($field, "The {$field} field format is invalid.");
                        }
                        break;
                    case 'uploaded':
                        if (!$this->isValidUpload($value)) {
                            $this->addError($field, "The {$field} file is required.");
                        }
                        break;
                    case 'maxFileSize':
                        if ($this->isValidUpload($value) && (int)$value['size'] > (int)$ruleValue) {
                            $this->addError($field, "The {$field} file is too large.");
                        }
                        break;
                    case 'mimes':
                        if ($this->isValidUpload($value)) {
                            $allowedMimes = explode(',', (string)$ruleValue);
                            $mime = is_file($value['tmp_name']) ? mime_content_type($value['tmp_name']) : false;
                            if ($mime === false || !in_array($mime, $allowedMimes, true)) {
                                $this->addError($field, "The {$field} file type is not allowed.");
                            }
                        }
                        break;
                }
            }
        }

        return empty($this->errors);
    }

    public function passes() {
        return empty($this->errors);
    }

    public function getErrors() {
        return $this->errors;
    }

    public function getFirstError() {
        if (empty($this->errors)) {
            return null;
        }
        $firstFieldErrors = reset($this->errors);
        return $firstFieldErrors[0] ?? null;
    }

    private function isEmpty($value): bool {
        return $value === null
            || (is_string($value) && trim($value) === '')
            || (is_array($value) && $value === []);
    }

    private function textLength(string $value): int {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

    private function valuesMatch($actual, $expected): bool {
        if ($expected === 'true') {
            return in_array($actual, [true, 1, '1', 'true'], true);
        }

        if ($expected === 'false') {
            return in_array($actual, [false, 0, '0', 'false'], true);
        }

        return is_scalar($actual) && (string)$actual === (string)$expected;
    }

    private function matchesDateFormat(string $value, string $format): bool {
        $date = \DateTime::createFromFormat('!' . $format, $value);
        $errors = \DateTime::getLastErrors();

        return $date !== false
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            && $date->format($format) === $value;
    }

    private function isValidUpload($value): bool {
        return is_array($value)
            && isset($value['error'], $value['tmp_name'], $value['size'])
            && (int)$value['error'] === UPLOAD_ERR_OK
            && is_string($value['tmp_name'])
            && $value['tmp_name'] !== '';
    }

    private function addError($field, $message) {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        if (!in_array($message, $this->errors[$field], true)) {
            $this->errors[$field][] = $message;
        }
    }
}

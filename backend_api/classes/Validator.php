<?php

class Validator
{
    private $errors = [];

    public function validate(array $data, array $rules): array
    {
        $this->errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $this->validateField($field, $value, $fieldRules);
        }

        return [
            'valid' => empty($this->errors),
            'errors' => $this->errors
        ];
    }

    private function validateField(string $field, $value, array $rules): void
    {
        foreach ($rules as $rule => $parameter) {
            if (is_numeric($rule)) {
                $rule = $parameter;
                $parameter = null;
            }

            switch ($rule) {
                case 'required':
                    if (!$this->isRequired($value)) {
                        $this->addError($field, "The $field field is required.");
                    }
                    break;

                case 'email':
                    if ($value && !$this->isEmail($value)) {
                        $this->addError($field, "The $field must be a valid email address.");
                    }
                    break;

                case 'min':
                    if ($value && !$this->isMinLength($value, $parameter)) {
                        $this->addError($field, "The $field must be at least $parameter characters.");
                    }
                    break;

                case 'max':
                    if ($value && !$this->isMaxLength($value, $parameter)) {
                        $this->addError($field, "The $field must not exceed $parameter characters.");
                    }
                    break;

                case 'numeric':
                    if ($value && !is_numeric($value)) {
                        $this->addError($field, "The $field must be a number.");
                    }
                    break;

                case 'integer':
                    if ($value && !filter_var($value, FILTER_VALIDATE_INT)) {
                        $this->addError($field, "The $field must be an integer.");
                    }
                    break;

                case 'date':
                    if ($value && !$this->isValidDate($value)) {
                        $this->addError($field, "The $field must be a valid date.");
                    }
                    break;

                case 'phone':
                    if ($value && !$this->isValidPhone($value)) {
                        $this->addError($field, "The $field must be a valid phone number.");
                    }
                    break;

                case 'blood_type':
                    if ($value && !$this->isValidBloodType($value)) {
                        $this->addError($field, "The $field must be a valid blood type (A+, A-, B+, B-, AB+, AB-, O+, O-).");
                    }
                    break;

                case 'age':
                    if ($value && !$this->isValidAge($value, $parameter)) {
                        $this->addError($field, "The $field must be between 18 and $parameter years.");
                    }
                    break;

                case 'weight':
                    if ($value && !$this->isValidWeight($value)) {
                        $this->addError($field, "The $field must be between 30 and 200 kg.");
                    }
                    break;

                case 'height':
                    if ($value && !$this->isValidHeight($value)) {
                        $this->addError($field, "The $field must be between 100 and 250 cm.");
                    }
                    break;
            }
        }
    }

    private function isRequired($value): bool
    {
        return !empty($value) || $value === '0';
    }

    private function isEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function isMinLength($value, int $min): bool
    {
        return strlen($value) >= $min;
    }

    private function isMaxLength($value, int $max): bool
    {
        return strlen($value) <= $max;
    }

    private function isValidDate(string $date): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    private function isValidPhone(string $phone): bool
    {
        return preg_match('/^[0-9+\-\s()]+$/', $phone);
    }

    private function isValidBloodType(string $bloodType): bool
    {
        $validTypes = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        return in_array(strtoupper($bloodType), $validTypes);
    }

    private function isValidAge($age, int $maxAge = 65): bool
    {
        if (!is_numeric($age)) {
            return false;
        }
        $age = (int) $age;
        return $age >= 18 && $age <= $maxAge;
    }

    private function isValidWeight($weight): bool
    {
        if (!is_numeric($weight)) {
            return false;
        }
        $weight = (float) $weight;
        return $weight >= 30 && $weight <= 200;
    }

    private function isValidHeight($height): bool
    {
        if (!is_numeric($height)) {
            return false;
        }
        $height = (int) $height;
        return $height >= 100 && $height <= 250;
    }

    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }
}

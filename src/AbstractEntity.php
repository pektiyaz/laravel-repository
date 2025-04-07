<?php

namespace Pektiyaz\LaravelRepository;

use Pektiyaz\RepositoryContracts\EntityContract;
use ReflectionClass;
use ReflectionMethod;

abstract class AbstractEntity implements EntityContract
{
    protected ?int $id = null;
    protected ?string $created_at = null;
    protected ?string $updated_at = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getCreatedAt(): string
    {
        return $this->created_at ?? '';
    }

    public function setCreatedAt(?string $created_at): void
    {
        $this->created_at = $created_at;
    }

    public function getUpdatedAt(): string
    {
        return $this->updated_at ?? '';
    }

    public function setUpdatedAt(?string $updated_at): void
    {
        $this->updated_at = $updated_at;
    }

    public function toArray(): array
    {
        $data = [];
        $methods = (new ReflectionClass($this))->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if ($method->getNumberOfRequiredParameters() !== 0) {
                continue;
            }

            $name = $method->name;

            if (str_starts_with($name, 'get')) {
                $key = lcfirst(substr($name, 3)); // getTitle → title
                $data[$key] = $this->$name();
            } elseif (str_starts_with($name, 'is')) {
                $key = lcfirst(substr($name, 2)); // isActive → active
                $data[$key] = $this->$name();
            }
        }

        return $data;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }

    public function fromArrayData(array $item): static
    {
        foreach ($item as $key => $value) {
            $camelKey = ucfirst($key); // 'active' → 'Active'

            $setter = 'set' . $camelKey;
            $altSetter = 'setIs' . $camelKey;

            if (method_exists($this, $setter)) {
                $this->$setter($value);
            } elseif (method_exists($this, $altSetter)) {
                $this->$altSetter($value);
            }
        }

        return $this;
    }


    public function fromEntity(object $item): static
    {
        if (!($item instanceof EntityContract)) {
            throw new \InvalidArgumentException("Object must implement EntityContract");
        }

        return $this->fromArrayData($item->toArray());
    }

    public function fromJson(string $json): static
    {
        $array = json_decode($json, true);
        return $this->fromArrayData($array ?? []);
    }
}

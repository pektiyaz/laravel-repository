<?php

namespace Pektiyaz\LaravelRepository;

use Pektiyaz\RepositoryContracts\RepositoryContract;
use Pektiyaz\RepositoryContracts\EntityContract;
use Pektiyaz\RepositoryContracts\QueryFilterContract;
use Illuminate\Support\Facades\Event;

abstract class AbstractRepository implements RepositoryContract
{
    protected  $model;
    protected string $event_prefix = '';
    protected EntityContract $entity;

    abstract public function getModel(): string;
    abstract public function getEntity(): string;
    abstract public function getEventPrefix(): string;

    public function __construct()
    {
        $this->model = new ($this->getModel())();
        $this->entity = new ($this->getEntity())();
        $this->event_prefix = $this->getEventPrefix();
    }

    /**
     * Find an entity by its primary ID.
     *
     * @param int|string $id
     * @return EntityContract|null
     */
    public function findById(int|string $id): ?EntityContract
    {
        $item = $this->model->find($id);

        return $item ? $this->convertToEntity($item) : null;
    }

    /**
     * Find a single entity that matches the given conditions.
     *
     * @param array $conditions
     * @return EntityContract|null
     */
    public function findOneBy(array $conditions): ?EntityContract
    {
        $item = $this->model->where($conditions)->first();

        return $item ? $this->convertToEntity($item) : null;
    }

    /**
     * Find all entities that match the given conditions.
     *
     * @param array $conditions
     * @return EntityContract[]
     */
    public function findAllBy(array $conditions): array
    {
        $items = $this->model->where($conditions)->get();

        return $items->map(fn($item) => $this->convertToEntity($item))->toArray();
    }

    /**
     * Retrieve all entities from the data source.
     *
     * @return EntityContract[]
     */
    public function findAll(): array
    {
        $items = $this->model->all();

        return $items->map(fn($item) => $this->convertToEntity($item))->toArray();
    }

    /**
     * Create a new entity with the given data.
     *
     * @param array $data
     * @return EntityContract
     */
    public function create(array $data): EntityContract
    {
        $item = $this->model->create($data);

        $entity = $this->convertToEntity($item);
        // Dispatch event after creation
        $this->dispatchEvent('created', $entity);

        return $entity;
    }

    /**
     * Update an existing entity by its ID with the given data.
     *
     * @param int|string $id
     * @param array $data
     * @return bool
     */
    public function update(int|string $id, array $data): bool
    {
        $item = $this->model->find($id);

        if ($item) {
            $item->update($data);
            $entity = $this->convertToEntity($item);
            $this->dispatchEvent('updated', $entity);
            return true;
        }

        return false;
    }

    /**
     * Delete an entity by its ID.
     *
     * @param int|string $id
     * @return bool
     */
    public function delete(int|string $id): bool
    {
        $item = $this->model->find($id);

        if ($item) {
            $entity = $this->convertToEntity($item);
            $item->delete();
            $this->dispatchEvent('deleted', $entity);
            return true;
        }

        return false;
    }

    /**
     * Convert the Eloquent model to EntityContract.
     *
     * @param mixed $item
     * @return EntityContract|null
     */
    protected function convertToEntity(mixed $item): ?EntityContract
    {
        return $item instanceof EntityContract ? $item : $this->mapToEntity($item);
    }

    /**
     * Map the Eloquent model to the EntityContract.
     *
     * @param object $item
     * @return EntityContract|null
     */
    protected function mapToEntity(object $item): ?EntityContract
    {
        return $this->entity->fromEntity($item);
    }

    /**
     * Check if any entity exists that matches the given conditions.
     *
     * @param array $conditions
     * @return bool
     */
    public function exists(array $conditions): bool
    {
        return $this->model->where($conditions)->exists();
    }

    /**
     * Count the number of entities that match the given conditions.
     *
     * @param array $conditions
     * @return int
     */
    public function count(array $conditions = []): int
    {
        return $this->model->where($conditions)->count();
    }

    /**
     * Restore a deleted entity
     *
     * @param int $id
     * @return EntityContract
     */
    public function restore(int $id): EntityContract
    {
        $item = $this->model->withTrashed()->find($id);
        $entity = $this->convertToEntity($item);
        if ($item) {
            $item->restore();
            Event::dispatch($this->event_prefix.'.entity.restored', $entity);
            $this->dispatchEvent('restored', $entity);
        }

        return $entity;
    }

    /**
     * Get a paginated list of entities.
     *
     * @param int $page
     * @param int $perPage
     * @param array $conditions
     * @return EntityContract[]
     */
    public function paginate(int $page, int $perPage, array $conditions = []): array
    {
        $items = $this->model->where($conditions)->paginate($perPage, ['*'], 'page', $page);

        return $items->map(fn($item) => $this->convertToEntity($item))->toArray();
    }

    /**
     * Find all soft-deleted entities.
     *
     * @return EntityContract[]
     */
    public function findTrashed(): array
    {
        $items = $this->model->onlyTrashed()->get();

        return $items->map(fn($item) => $this->convertToEntity($item))->toArray();
    }

    /**
     * Find a trashed entity by its ID.
     *
     * @param int|string $id
     * @return EntityContract|null
     */
    public function findTrashedById(int|string $id): ?EntityContract
    {
        $item = $this->model->onlyTrashed()->find($id);

        return $item ? $this->convertToEntity($item) : null;
    }

    /**
     * Permanently delete a soft-deleted entity.
     *
     * @param int|string $id
     * @return bool
     */
    public function forceDelete(int|string $id): bool
    {
        $item = $this->model->find($id);

        if ($item) {
            $entity = $this->convertToEntity($item);
            $item->forceDelete();
            $this->dispatchEvent('permanently_deleted', $entity);
            return true;
        }

        return false;
    }

    /**
     * Find entities by a callback or closure (for complex filtering).
     *
     * @param callable $callback
     * @return EntityContract[]
     */
    public function findByCallback(callable $callback): array
    {
        $items = $this->model->where($callback)->get();

        return $items->map(fn($item) => $this->convertToEntity($item))->toArray();
    }

    /**
     * Create multiple entities at once.
     *
     * @param array $records
     * @return EntityContract[]
     */
    public function bulkCreate(array $records): array
    {
        $createdItems = [];

        foreach ($records as $record) {
            $item = $this->model->create($record);
            $entity = $this->convertToEntity($item);
            $this->dispatchEvent('created', $entity);
            $createdItems[] = $entity;
        }

        return $createdItems;
    }

    /**
     * Update multiple entities by conditions.
     *
     * @param array $conditions
     * @param array $data
     * @return int
     */
    public function bulkUpdate(array $conditions, array $data): int
    {
        return $this->model->where($conditions)->update($data);
    }

    /**
     * Delete multiple entities by conditions.
     *
     * @param array $conditions
     * @return int
     */
    public function bulkDelete(array $conditions): int
    {
        return $this->model->where($conditions)->delete();
    }

    /**
     * Use a filter object to retrieve entities.
     *
     * @param QueryFilterContract $filter
     * @return EntityContract[]
     */
    public function filter(QueryFilterContract $filter): array
    {
        $items = $this->model->filter($filter)->get();

        return $items->map(fn($item) => $this->convertToEntity($item))->toArray();
    }


    /**
     * Use a filter object to retrieve count of entities.
     *
     * @param QueryFilterContract $filter
     * @return int
     */
    public function countByFilter(QueryFilterContract $filter): int
    {
        return $this->model->filter($filter)->count();
    }

    /**
     * Delete using filter object.
     *
     * @param QueryFilterContract $filter
     * @return int
     */
    public function deleteByFilter(QueryFilterContract $filter): int
    {
        return $this->model->filter($filter)->delete();
    }

    /**
     * Update using filter object.
     *
     * @param QueryFilterContract $filter
     * @param array $data
     * @return int
     */
    public function updateByFilter(QueryFilterContract $filter, array $data): int
    {
        return $this->model->filter($filter)->update($data);
    }

    /**
     * Dispatches a domain event for the given entity.
     *
     * @param string $event
     * @param EntityContract $entity
     */
    protected function dispatchEvent(string $event, EntityContract $entity): void
    {
        Event::dispatch($this->event_prefix . '.entity.' . $event, $entity);
    }
}

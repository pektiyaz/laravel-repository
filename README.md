# Laravel Repository 🧱

A clean, extendable, and event-driven repository pattern implementation for Laravel applications. Easily separate your business logic from the persistence layer using a simple and powerful abstraction.

> Built with ❤️ by [Pektiyaz](https://github.com/pektiyaz)

---

## ✨ Features

- 🧩 Abstract base repository with out-of-the-box CRUD operations
- 🔁 Event-driven architecture (`created`, `updated`, `deleted`, `restored`, etc.)
- ♻️ Soft delete & restore support
- 🔎 Powerful filtering with custom `QueryFilterContract`
- 🗃️ Entity abstraction with transformation helpers (`toArray`, `toJson`, etc.)
- 📦 Bulk operations (create, update, delete)
- 📖 Pagination and advanced query support via callbacks

---

## 📦 Installation

```bash
composer require pektiyaz/laravel-repository
```

## 🧰 Usage
1. Extend the AbstractRepository

```php
use Pektiyaz\LaravelRepository\AbstractRepository;

class PostRepository extends AbstractRepository
{
    public function getModel(): string
    {
        return \App\Models\Post::class;
    }

    public function getEntity(): string
    {
        return \App\Entities\PostEntity::class;
    }

    public function getEventPrefix(): string
    {
        return 'post';
    }
}
```


2. Create Your Entity

```php

use Pektiyaz\LaravelRepository\AbstractEntity;

class PostEntity extends AbstractEntity
{
    protected ?string $title = null;

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    // Add other fields as needed...
}
```



## 🧠 Concepts
#### 📂 AbstractRepository
The AbstractRepository provides a fully-featured base to handle:

- findById, findOneBy, findAllBy, findAll
- create, update, delete
- restore, forceDelete
- bulkCreate, bulkUpdate, bulkDelete
- paginate, exists, count
- filter, updateByFilter, deleteByFilter, countByFilter

Event dispatching with customizable prefixes
#### 🧱 AbstractEntity
The AbstractEntity provides a structured way to transform data between model and entity:

- toArray(), toJson() → Serialize entity
- fromArrayData(array $data) → Hydrate entity
- fromEntity(object $item) → Populate from another entity
- fromJson(string $json) → Load from JSON
#### 📋 Example Event Dispatching
If your repository uses an event prefix post, the following events will be dispatched automatically:

- post.entity.created
- post.entity.updated
- post.entity.deleted
- post.entity.restored
- post.entity.permanently_deleted

Use Laravel’s event listeners to handle these events for logging, syncing, notifications, etc.

#### 🔧 Contracts Required
This package relies on a few contracts to ensure consistency:

1. RepositoryContract
2. EntityContract
3. QueryFilterContract

These can be published or extended as needed for your application structure.
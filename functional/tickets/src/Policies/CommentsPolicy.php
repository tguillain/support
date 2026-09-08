<?php

namespace Functional\Tickets\Policies;

use App\Models\User;
use Functional\Tickets\Models\Comment;

class CommentsPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Comment $comment): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Comment $comment): bool
    {
        return $user->getKey() === $comment->author_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Comment $comment): bool
    {
        return $user->getKey() === $comment->author_id;
    }

    /**
     * Determine whether the user can attach an author to a comment.
     */
    public function attachUser(User $user, Comment $comment, User $attached): bool
    {
        return true;
    }
}

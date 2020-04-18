<?php

namespace App\JsonRpc\Contracts\Comment;

interface CommentServiceInterface {
    public function getList(int $page, int $perPage);
}
<?php
require_once '../functions.php';
require_once 'Comment.php';

/**
 * @param Comment[] $comments
 */
function print_comments(array $comments, &$output)
{
    foreach ($comments as $comment) {
        for ($level_count = 0; $level_count < $comment->level; $level_count++)
            $output .= '-';
        $output .= $comment->id;
        if ($comment->is_freeze) $output .= '*';
        $output .= "\r\n";
        print_comments($comment->childrens, $output);
    }
}

function freeze(Comment $comment)
{
    $comment->is_freeze = true;
    foreach ($comment->childrens as $children_comment)
        freeze($children_comment);
}

function unfreeze(Comment $comment)
{
    $comment->is_freeze = false;
    foreach ($comment->childrens as $children_comment)
        freeze($children_comment);
}


$input_content = file_get_contents('input.txt');
$lines = explode("\r", $input_content);

/** @var Comment[] $comments_links */
$comments = []; // Здесь храним комментарии согласно иерархии

/** @var Comment[] $comments_links */
$comments_links = []; // Здесь храним ссылки на комментарии без учёта иерархии дабы не лезть в рекурсию


$commands = [];

foreach ($lines as $line) {
    $line_parts = explode(' ', $line);
    $command = new stdClass();
    $command->name = trim($line_parts[0]);
    if ($command->name == 'add') {
        $command->comment_id = $line_parts[1];
        $command->parent_id = $line_parts[2];
    } else
        $command->target = $line_parts[1];
    $commands[] = $command;
}


$is_freeze_all = false;
foreach ($commands as $command) {
    if ($command->name == 'add') {
        if ($is_freeze_all) // Ничего не делаем, если всё заморожено
            continue;
        // Формируем комментарий
        $comment = new Comment();
        $comment->id = $command->comment_id;
        $comment->parent_id = $command->parent_id;
        if ($comment->parent_id == '-') { // Если это комментарий в родительскую ветку
            $comment->level = 0;
            $comments[] = $comment;
            $comments_links[$comment->id] = $comment;
        } else { // Если комментарий является ответом
            if (!$comments_links[$comment->parent_id]->is_freeze) { // Если ветка не заморожена
                $comment->level = $comments_links[$comment->parent_id]->level + 1;
                $comments_links[$comment->parent_id]->childrens[] = $comment;
                $comments_links[$comment->id] = $comment;
            }
        }
    } elseif ($command->name == 'freeze') { // Замораживаем
        if ($command->target == '-') { // Все комментарии
            $is_freeze_all = true;
            foreach ($comments_links as $comment)
                $comment->is_freeze = true;
        } else // Конкретную ветку
            freeze($comments_links[$command->target]);
    } elseif ($command->name == 'unfreeze') {
        if ($command->target == '-') { // Аналогично заморозке, размораживаем
            $is_freeze_all = false;
            foreach ($comments_links as $comment)
                $comment->is_freeze = false;
        } else
            unfreeze($comments_links[$command->target]);
    }
}


$output_content = '';
print_comments($comments, $output_content);
file_put_contents('output.txt', $output_content);

echo nl2br($output_content);
dd($commands, 'Исходные команды');
dd($comments, 'Комментарии на выходе');

//test
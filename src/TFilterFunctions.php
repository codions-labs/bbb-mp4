<?php

namespace ProfIT\Bbb;

use ProfIT\Bbb\Events\ChatEvent;
use ProfIT\Bbb\Events\DeskshareEvent;
use ProfIT\Bbb\Events\PresentationEvent;
use ProfIT\Bbb\Events\UserEvent;
use ProfIT\Bbb\Events\WebcamEvent;
use Runn\Core\Collection;
use Runn\Core\Std;

/**
 * Class TFilterFunctions
 * @package ProfIT\Bbb
 *
 * @mixin Process
 */
trait TFilterFunctions
{
    /**
     * @param Collection|PresentationEvent[] $events
     */
    protected function preparePresentationFilters(Collection $events)
    {
        $window = $this->layout->getWindowByName('PresentationWindow');
        foreach ($events as $key => $event) {
            $slide = $this->dst . 'slides/' . $event->getPath();
            list($slideWidth, $slideHeight) = getimagesize($slide);
            $this->ffmpeg->addImageSource($slide);
            $this->ffmpeg->addImageFilter(
                ($event->time - $this->sound->getStartTime()) / 1000,
                isset($events[$key + 1]) ? (($events[$key + 1]->time - $this->sound->getStartTime()) / 1000) : '100000',
                $window->x + $window->pad,
                round($window->y + (($window->h - $slideHeight) / 2) + $window->pad)
            );
        }
    }

    /**
     * @param Collection|UserEvent[] $events
     */
    protected function prepareUserFilters(Collection $events)
    {
        $users = [];
        $context = $this->layout->getWindowByName('UsersWindow');

        foreach ($events as $key => $event) {
            if ('join' === $event->type) {
                $users[$event->uid] = $event->uname;
            } elseif ('left' === $event->type) {
                unset($users[$event->uid]);
            } else {
                continue;
            }

            $image = $this->dst . 'users/list.' . $event->time . '.png';
            $this->generateUsersListImage($image, $context, $users);
            $this->ffmpeg->addImageSource($image);
            $this->ffmpeg->addImageFilter(
                ($event->time - $this->sound->getStartTime()) / 1000,
                isset($events[$key + 1]) ? (($events[$key + 1]->time - $this->sound->getStartTime()) / 1000) : '100000',
                $context->x,
                $context->y
            );
        }
    }

    /**
     * @param Collection|ChatEvent[] $events
     */
    protected function prepareChatFilters(Collection $events)
    {
        $messages = new Collection();

        $context = $this->layout->getWindowByName('ChatWindow');

        $dstPath = $this->dst . 'chat/';
        /** Chat caption from start */
        $image = $dstPath . 'list.' . $this->sound->getStartTime() . '.png';
        $this->generateChatListImage($image, $context, $messages);
        $this->ffmpeg->addImageSource($image);
        $this->ffmpeg->addImageFilter(
            0,
            isset($events[0]) ? (($events[0]->time - $this->sound->getStartTime()) / 1000) : '100000',
            $context->x,
            $context->y
        );

        foreach ($events as $key => $event) {
            $messages->append($event);
            $image = $dstPath . 'list.' . $event->time . '.png';
            $this->generateChatListImage($image, $context, $messages);
            $this->ffmpeg->addImageSource($image);
            $this->ffmpeg->addImageFilter(
                ($event->time - $this->sound->getStartTime()) / 1000,
                isset($events[$key + 1]) ? (($events[$key + 1]->time - $this->sound->getStartTime()) / 1000) : '100000',
                $context->x,
                $context->y
            );
        }
    }

    /**
     * @param Collection|WebcamEvent[] $events
     */
    protected function prepareWebcamFilters(Collection $events)
    {
        $meetingId = $this->events->findMeetingId();
        if (null === $meetingId) {
            $this->error('Meeting ID not found');
        }

        $content = $this->layout->getWindowByName('VideoDock')->getContentCoordinates();

        $fragments = [];
        foreach ($events as $event) {
            if ('start' === $event->type) {
                $fragments[$event->file] = new Std([
                    'start' => ($event->time - $this->sound->getStartTime()) / 1000,
                    'end' => '100000',
                    'source' => $this->src . 'video/' . $meetingId . '/' . $event->file . '.flv',
                ]);
            } elseif ('stop' === $event->type) {
                $fragments[$event->file]->end = ($event->time - $this->sound->getStartTime()) / 1000;
            }
        }

        foreach ($fragments as $key => $video) {
            $this->ffmpeg->addVideoSource($video);

            $source = $this->getVideoResizedDimensions($video->source, $content->w, $content->h);

            $this->ffmpeg->addVideoFilter($video, new Std([
                'w' => $source->w,
                'h' => $source->resized ? $source->h : '-1',
                'x' => round($content->x + ($content->w - $source->w) / 2),
                'y' => round($content->y + ($content->h - $source->h) / 2),
            ]));
        }
    }

    /**
     * @param Collection|DeskshareEvent[] $events
     */
    protected function prepareDeskshareFilters(Collection $events)
    {
        $eventsCount = 0;

        $fragments = [];
        foreach ($events as $event) {
            /** @var DeskshareEvent $event */
            if ('started' === $event->type) {
                $eventsCount++;
                $fragments[$eventsCount] = new Std([
                    'start' => ($event->time - $this->sound->getStartTime()) / 1000,
                    'end' => '100000',
                    'source' => $this->src . 'deskshare/' . basename($event->file),
                ]);
            } elseif ('stopped' === $event->type) {
                $fragments[$eventsCount]->end = ($event->time - $this->sound->getStartTime()) / 1000;
            }
        }

        foreach ($fragments as $key => $video) {
            $image = $this->dst . 'deskshare/deskshare-' . $key . '.png';
            list($coords, $resized) = $this->makeDeskshareLayout($video->source, $image, 10);

            $this->ffmpeg->addImageSource($image);
            $this->ffmpeg->addImageFilter($video->start, $video->end, $coords->x, $coords->y);

            $this->ffmpeg->addVideoSource($video);

            $this->ffmpeg->addVideoFilter($video, new Std([
                'w' => $coords->w,
                'h' => $resized ? $coords->h : '-1',
                'x' => $coords->x,
                'y' => $coords->y,
            ]));
        }
    }
}

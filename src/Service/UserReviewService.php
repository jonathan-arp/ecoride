<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Review;
use App\Repository\ReviewRepository;
class UserReviewService
{
    public function __construct(
        private ReviewRepository $reviewRepository
    ) {}

    /**
     * Get reviews received by user as driver
     */
    public function getReceivedReviews(User $user): array
    {
        return $this->reviewRepository->findBy(['driver' => $user]);
    }

    /**
     * Get reviews given by user as passenger
     */
    public function getGivenReviews(User $user): array
    {
        return $this->reviewRepository->findBy(['passenger' => $user]);
    }

    /**
     * Get average rating as driver (published reviews only)
     */
    public function getAverageRating(User $user): ?float
    {
        $publishedReviews = $this->reviewRepository->findBy([
            'driver' => $user,
            'published' => true
        ]);
        
        if (empty($publishedReviews)) {
            return null;
        }

        $total = 0;
        foreach ($publishedReviews as $review) {
            $total += $review->getRating();
        }

        return round($total / count($publishedReviews), 1);
    }

    /**
     * Count published reviews received as driver
     */
    public function getPublishedReviewsCount(User $user): int
    {
        return $this->reviewRepository->count([
            'driver' => $user,
            'published' => true
        ]);
    }

    /**
     * Add a review given by user as passenger
     */
    public function addGivenReview(User $user, Review $review): void
    {
        $review->setPassenger($user);
        // The review will be persisted by the calling controller
    }

    /**
     * Add a review received by user as driver
     */
    public function addReceivedReview(User $user, Review $review): void
    {
        $review->setDriver($user);
        // The review will be persisted by the calling controller
    }
}

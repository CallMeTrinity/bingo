<?php

namespace App\Security\Voter;

use App\Entity\Bingo;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class BingoVoter extends Voter
{
    public const string EDIT = 'BINGO_EDIT';
    public const string VIEW = 'BINGO_VIEW';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::VIEW], true)
            && $subject instanceof Bingo;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            $vote?->addReason('The user must be logged in to access this resource.');

            return false;
        }

        return match ($attribute) {
            self::VIEW => $this->canView($subject, $user, $vote),
            self::EDIT => $this->canEdit($subject, $user, $vote),
        };
    }

    public function canView(Bingo $bingo, UserInterface $user, ?Vote $vote = null): bool
    {
        if ($this->canEdit($bingo, $user)) {
            return true;
        }

        $vote?->addReason('User does not have permission to view this bingo.');
        return false;
    }

    public function canEdit(Bingo $bingo, UserInterface $user, ?Vote $vote = null): bool
    {
        if ($user === $bingo->getOwner()) {
            return true;
        }

        $vote?->addReason(sprintf('User %s is not the owner of the bingo %s', $user->getUserIdentifier(), $bingo->getId()));
        return false;

    }
}

<?php
namespace App\Security;

use App\Entity\Entreprise;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class EntrepriseVoter extends Voter
{
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE]) && $subject instanceof Entreprise;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }
        /** @var Entreprise $entreprise */
        $entreprise = $subject;
        switch ($attribute) {
            case self::EDIT:
            case self::DELETE:
                return $entreprise->getUser() === $user;
        }
        return false;
    }
}

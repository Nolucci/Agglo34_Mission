<?php

namespace App\Twig;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PermissionExtension extends AbstractExtension
{
    public function __construct(
        private Security $security
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('can_access_phone_lines', [$this, 'canAccessPhoneLines']),
            new TwigFunction('can_access_equipment', [$this, 'canAccessEquipment']),
            new TwigFunction('can_access_boxes', [$this, 'canAccessBoxes']),
            new TwigFunction('can_modify', [$this, 'canModify']),
            new TwigFunction('can_manage_users', [$this, 'canManageUsers']),
            new TwigFunction('is_admin', [$this, 'isAdmin']),
            new TwigFunction('is_modifieur', [$this, 'isModifieur']),
        ];
    }

    public function canAccessPhoneLines(): bool
    {
        $user = $this->security->getUser();
        return $user instanceof User && $user->canAccessPhoneLines();
    }

    public function canAccessEquipment(): bool
    {
        $user = $this->security->getUser();
        return $user instanceof User && $user->canAccessEquipment();
    }

    public function canAccessBoxes(): bool
    {
        $user = $this->security->getUser();
        return $user instanceof User && $user->canAccessBoxes();
    }

    public function canModify(): bool
    {
        $user = $this->security->getUser();
        return $user instanceof User && $user->canModify();
    }

    public function canManageUsers(): bool
    {
        $user = $this->security->getUser();
        return $user instanceof User && $user->canManageUsers();
    }

    public function isAdmin(): bool
    {
        $user = $this->security->getUser();
        return $user instanceof User && $user->isAdmin();
    }

    public function isModifieur(): bool
    {
        $user = $this->security->getUser();
        return $user instanceof User && $user->isModifieur();
    }
}
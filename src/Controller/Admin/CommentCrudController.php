<?php

namespace App\Controller\Admin;

use App\Entity\Comment;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;

class CommentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Comment::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        $crud
            ->setEntityLabelInSingular('Comment')
            ->setEntityLabelInPlural('Comments')
            ->setSearchFields(['author', 'text', 'email'])
            ->setDefaultSort(['createdAt' => 'DESC'])
        ;

        return $crud;
    }

    public function configureFilters(Filters $filters): Filters
    {
        $filters->add(EntityFilter::new('conference'));
        return $filters;
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('conference');
        yield TextField::new('author');
        yield EmailField::new('email');
        yield TextareaField::new('text')
            ->hideOnIndex()
        ;
        yield ImageField::new('photoFileName')
            ->setBasePath('/uploads/photos')
            ->setLabel('Photo')
            ->onlyOnIndex()
        ;
        yield DateTimeField::new('createdAt')
            ->onlyOnIndex()
        ;

        yield ChoiceField::new('state')
            ->setChoices([
                'Submitted' => 'submitted',
                'Ham' => 'ham',
                'Potential spam' => 'potential_spam',
                'Spam' => 'spam',
                'Rejected' => 'rejected',
                'Published' => 'published',
            ])
        ;

        if (Crud::PAGE_EDIT === $pageName) {
            yield DateTimeField::new('createdAt')
                ->setFormTypeOption('disabled', true)
            ;
        }
    }
}

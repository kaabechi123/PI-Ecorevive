<?php 
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichFileType;
use App\Entity\User;

class AvatarType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('avatarFile', VichFileType::class, [
                'label' => 'Upload Avatar',
                'required' => false,
                'allow_delete' => true, // Allow users to delete the avatar
                'download_uri' => false, // Hide the download link
                'delete_label' => 'Remove Avatar', // Label for the delete checkbox
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class, // Bind this form to the User entity
        ]);
    }
}
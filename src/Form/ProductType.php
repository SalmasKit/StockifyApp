<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Product;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
       $builder
           ->add('name', TextType::class)
           ->add('description', TextareaType::class)
           ->add('price', MoneyType::class, [
               'currency' => 'MAD',
               'html5' => true,
               'attr' => [
                   'step' => '0.01',
                   'class' => 'w-full border-2 rounded-lg px-4 py-2 focus:border-blue-500 outline-none'
               ],
           ])
           ->add('quantity', NumberType::class, [
               'attr' => ['min' => 0] // HTML5 number picker
           ])
           ->add('unit', ChoiceType::class, [
               'choices'  => [
                   'Grams (g)' => 'g',
                   'Kilograms (kg)' => 'kg',
                   'Liters (l)' => 'l',
                   'Meters (m)' => 'm',
                   'Centimeters (cm)' => 'cm',
                   'Hours (h)' => 'h',
                   'Pieces (piece)' => 'piece',
               ],
           ])
           ->add('image', FileType::class, [
               'label' => 'Product Image',
               'mapped' => false,
               'required' => false,
           ])
           ->add('category', EntityType::class, [
               'class' => Category::class,
               'choice_label' => 'name',
           ]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}

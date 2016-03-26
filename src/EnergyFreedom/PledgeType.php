<?php
namespace EnergyFreedom\Pledge\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class PledgeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
           ->add('pledge')
           ->add('full_name')
           ->add('house_type', 'choice', array(
               'choices' => array(
                   'Stand alone home', 'Semi-detached home', 'Town house',
                   'Apartment', 'Rural'
               )
           ))
           ->add('house_type_other', 'text', array(
               'required' => false
           ))
           ->add('brick_veneer')
           ->add('double_brick')
           ->add('weatherboard')
           ->add('al_cladding')
           ->add('panels')
           ->add('wall_construction_other')
           ->add('wall_batts', 'checkbox')
           ->add('wall_infill', 'checkbox')
           ->add('wall_foil', 'checkbox')
           ->add('no_ins', 'checkbox')
           ->add('wall_insulation_other')
           ->add('tiles')
           ->add('corrugated_metal')
           ->add('slate')
           ->add('roof_construction_other')
           ->add('roof_batts')
           ->add('roof_infill')
           ->add('roof_foil')
           ->add('no_roof_ins')
           ->add('roof_insulation_other')
           ->add('slab_on_ground')
           ->add('slab_off_ground')
           ->add('timber_boards')
           ->add('floor_construction_other')
           ->add('floor_batts')
           ->add('floor_infill')
           ->add('floor_foil')
           ->add('no_floor_ins')
           ->add('floor_insulation_other')
           ->add('rooms_carpeted')
           ->add('rooms_tiled')
           ->add('rooms_timber_floors')
           ->add('windows_glazing', 'choice', array(
               'choices' => array(
                   'Single glazed', 'Single glazed with reflective coating',
                   'Double glazed', 'Triple Glazed'
               )
           ))
           ->add('window_frame', 'choice', array(
               'choices' => array(
                   'Timber', 'Aluminium', 'Plastic (uPVC)'
               )
           ))
           ->add('window_frame_other')
           ->add('window_coverings_internal', 'choice', array(
               'choices' => array(
                   'None', 'Venetian blinds', 'Block-out blinds', 'Light fabric blinds',
                   'Heavy fabric blinds'
               )
           ))
           ->add('other_internal')
           ->add('window_coverings_internal_pelmets')
           ->add('no_pelmets')
           ->add('window_coverings_external', 'choice', array(
               'choices' => array(
                   'Awnings', 'Natural shading'
               )
           ))
           ->add('other_external')
           ->add('heating_gas_ducted')
           ->add('heating_gas_room')
           ->add('heating_electric_ducted')
           ->add('heating_electric_wall_radiator')
           ->add('heating_electric_portable_radiator')
           ->add('heating_electric_oil_bar_header')
           ->add('heating_hydronic_heating_wall')
           ->add('heating_hydronic_heating_slab')
           ->add('heating_reverse_cycle_pre_2000')
           ->add('heating_reverse_cycle_post_2000')
           ->add('heating_wood_fired')
           ->add('cooling_reverse_cycle_pre_2000')
           ->add('cooling_reverse_cycle_post_2000')
           ->add('cooling_ceiling_fan')
           ->add('cooling_ducted_evaporative_cooling')
           ->add('cooling_room_evaporative_cooling')
           ->add('cooking_gas_cooktop')
           ->add('cooking_electric_ceramic_cooktop')
           ->add('cooking_induction_cooktop')
           ->add('cooking_electric_oven')
           ->add('cooking_gas_oven')
           ->add('hot_water', 'choice', array(
               'choices' => array(
                   'Solar with gas booster', 'Solar with electric booster',
                   'Solar with no boost', 'Gas', 'Instant Gas', 'Electric',
                   'Electric heat pump'
               )
           ))
           ->add('energy_production', 'choice', array(
               'choices' => array(
                   'Thinking about generating a portion of your electricity needs',
                   'Currently installing ways to produce your electricity needs',
                   'Already producing some of your own electricity',
                   'Producing all of your own electricity',
                   'Producing an excess of electricity'
               )
           ))
           ->add('action_display')
           ->add('action_led')
           ->add('action_draft_proofing')
           ->add('action_reverse_cycle')
           ->add('action_efficiency_appliances')
           ->add('action_double_glazing')
           ->add('action_wall_insulation')
           ->add('action_solar')
           ->add('solar_amount', 'choice', array(
               'choices' => array(
                   '1kw', '1.5kw', '2kw', '2.5kw', '3kw', '3.5kw', '4kw', '4.5kw',
                   '5kw', 'over 5kw'
               )
           ))
           ->add('volunteer_signup_content')
           ->add('submit', 'submit');
    }

    public function getName()
    {
        return 'pledge';
    }
}

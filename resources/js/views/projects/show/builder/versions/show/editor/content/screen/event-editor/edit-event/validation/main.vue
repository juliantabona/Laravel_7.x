<template>

    <div>

        <!-- Validation Rules Instruction -->
        <Alert v-if="!validationRulesExist" type="info" style="line-height: 1.4em;" class="mb-2" closable>
            Add <span class="font-italic text-success font-weight-bold">Validation Rules</span> to only
            allow the user to provide specific type of information e.g 
            <span class="font-italic text-success font-weight-bold">Only Numbers</span> are allowed for Age or
            <span class="font-italic text-success font-weight-bold">Only Letters</span> are allowed for First Name.
        </Alert>

        <div class="bg-grey-light border mt-2 mb-2 p-2">

            <textOrCodeEditor
                size="small"
                title="Target"
                :value="event.event_data.target"
                :placeholder="'{{ product.quantity }}'"
                sampleCodeTemplate="ussd_service_select_option_no_options_found_msg_sample_code">
            </textOrCodeEditor>
            
        </div>

        <!-- Validation Rule List & Dragger  -->
        <draggable
            :list="validationRules"
            @start="drag=true" 
            @end="drag=false" 
            :options="{
                group:'validation_rules', 
                handle:'.dragger-handle',
                draggable:'.single-draggable-item',
            }">

            <!-- Single Validation Rule  -->
            <singleValidationRule v-for="(validationRule, index) in validationRules" :key="validationRule.id+'_'+index"
                :validationRules="validationRules"
                :validationRule="validationRule"
                :builder="builder"
                :display="display"
                :screen="screen"
                :index="index">
            </singleValidationRule>
            
            <!-- No validation rules message -->
            <Alert v-if="!validationRulesExist" type="info" class="mb-0" style="width:300px;" show-icon>No Validation Rules Found</Alert>

        </draggable>

        <div class="clearfix">

            <!-- Add Validation Rule Button -->
            <basicButton :type="addButtonType" size="default" icon="ios-add" :showIcon="true"
                            class="float-right" :ripple="!validationRulesExist"
                            @click.native="handleAddValidationRule()">
                <span>Add Validation Rule </span>
            </basicButton>

        </div>

        <!-- 
            MODAL TO ADD VALIDATION RULE
        -->
        <template v-if="isOpenAddValidationRuleModal">

            <createValidationRuleModal
                :validationRules="validationRules"
                @visibility="isOpenAddValidationRuleModal = $event">
            </createValidationRuleModal>
    
        </template>

    </div>

</template>

<script>

    import draggable from 'vuedraggable';
    import textOrCodeEditor from './../../../textOrCodeEditor.vue';
    import singleValidationRule from './single-validation-rule/main.vue';
    import createValidationRuleModal from './create-validation-rule/createValidationRuleModal.vue';
    import basicButton from './../../../../../../../../../../../../components/_common/buttons/basicButton.vue';

    export default {
        components: { draggable, textOrCodeEditor, singleValidationRule, createValidationRuleModal, basicButton },
        props: { 
            event: {
                type: Object,
                default:() => {}
            },
            events: {
                type: Array,
                default: () => []
            },
            display: {
                type: Object,
                default:() => {}
            },
            screen: {
                type: Object,
                default:() => {}
            },
            builder: {
                type: Object,
                default: () => {}
            },
        },
        data(){
            return {
                isOpenAddValidationRuleModal: false,
                validationRules: this.event.event_data.rules
            }
        },
        computed: {
            totalValidationRules(){
                return this.validationRules.length;
            },
            validationRulesExist(){
                return this.totalValidationRules ? true : false;
            },
            addButtonType(){
                return this.validationRulesExist ? 'default' : 'success';
            }
        },
        methods: {
            handleAddValidationRule(){
                this.isOpenAddValidationRuleModal = true;
            }
        }
    };
  
</script>
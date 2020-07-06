<template>

    <div>

        <!-- Formatting Rules Instruction -->
        <Alert v-if="!formattingRulesExist" type="info" style="line-height: 1.4em;" class="mb-2" closable>
            Add <span class="font-italic text-success font-weight-bold">Formatting Rules</span> to only
            allow the user to provide specific type of information e.g 
            <span class="font-italic text-success font-weight-bold">Only Numbers</span> are allowed for Age or
            <span class="font-italic text-success font-weight-bold">Only Letters</span> are allowed for First Name.
        </Alert>

        <!-- Target -->
        <textOrCodeEditor
            size="small"
            title="Target"
            class="mt-2 mb-2"
            :value="event.event_data.target"
            :placeholder="'{{ product.quantity }}'"
            sampleCodeTemplate="ussd_service_select_option_no_options_found_msg_sample_code">
        </textOrCodeEditor>

        <!-- Reference Name Input -->
        <referenceNameInput 
            class="mb-2"
            v-model="event.event_data.reference_name"
            :builder="builder" :screen="screen" :display="display"
            title="Alternative Reference:" :inlineLayout="false">
        </referenceNameInput>

        <!-- Formatting Rule List & Dragger  -->
        <draggable
            :list="formattingRules"
            @start="drag=true" 
            @end="drag=false" 
            :options="{
                group:'formatting_rules', 
                handle:'.dragger-handle',
                draggable:'.single-draggable-item',
            }">

            <!-- Single Formatting Rule  -->
            <singleFormattingRule v-for="(formattingRule, index) in formattingRules" :key="formattingRule.id+'_'+index"
                :formattingRules="formattingRules"
                :formattingRule="formattingRule"
                :builder="builder"
                :display="display"
                :screen="screen"
                :index="index">
            </singleFormattingRule>
            
            <!-- No formatting rules message -->
            <Alert v-if="!formattingRulesExist" type="info" class="mb-0" style="width:300px;" show-icon>No Formatting Rules Found</Alert>

        </draggable>

        <div class="clearfix">

            <!-- Add Formatting Rule Button -->
            <basicButton :type="addButtonType" size="default" icon="ios-add" :showIcon="true"
                            class="float-right" :ripple="!formattingRulesExist"
                            @click.native="handleAddFormattingRule()">
                <span>Add Formatting Rule </span>
            </basicButton>

        </div>

        <!-- 
            MODAL TO ADD FORMATTING RULE
        -->
        <template v-if="isOpenAddFormattingRuleModal">

            <createFormattingRuleModal
                :formattingRules="formattingRules"
                @visibility="isOpenAddFormattingRuleModal = $event">
            </createFormattingRuleModal>
    
        </template>

    </div>

</template>

<script>

    import draggable from 'vuedraggable';
    import textOrCodeEditor from './../../../textOrCodeEditor.vue';
    import referenceNameInput from './../../../referenceNameInput.vue';
    import singleFormattingRule from './single-formatting-rule/main.vue';
    import createFormattingRuleModal from './create-formatting-rule/createFormattingRuleModal.vue';
    import basicButton from './../../../../../../../../../../../../components/_common/buttons/basicButton.vue';

    export default {
        components: { 
            draggable, textOrCodeEditor, referenceNameInput, singleFormattingRule, 
            createFormattingRuleModal, basicButton
        },
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
                isOpenAddFormattingRuleModal: false,
                formattingRules: this.event.event_data.rules
            }
        },
        computed: {
            totalFormattingRules(){
                return this.formattingRules.length;
            },
            formattingRulesExist(){
                return this.totalFormattingRules ? true : false;
            },
            addButtonType(){
                return this.formattingRulesExist ? 'default' : 'success';
            }
        },
        methods: {
            handleAddFormattingRule(){
                this.isOpenAddFormattingRuleModal = true;
            }
        }
    };
  
</script>
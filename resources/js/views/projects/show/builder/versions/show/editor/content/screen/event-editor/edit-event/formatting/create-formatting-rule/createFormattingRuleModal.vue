<template>
    <div>
        <!-- Modal 

             Note: modalVisible and detectClose() are imported from the modalMixin.
             They are used to allow for opening and closing the modal properly
             during the v-if conditional statement of the parent component. It
             is important to note that <Modal> does not open/close well with
             v-if statements by default, therefore we need to add additional
             functionality to enhance the experience. Refer to modalMixin.
        -->
        <Modal
            width="800"
            v-model="modalVisible"
            title="Add Formatting Rule"
            @on-visible-change="detectClose">
            
            <!-- Header -->
            <Row :gutter="4" class="bg-grey-light m-0 p-2">
                
                <Col :span="6">
                    <span class="font-weight-bold text-dark">Rule</span>
                </Col>
                
                <Col :span="18">
                    <span class="font-weight-bold text-dark">Description</span>
                </Col>

            </Row>

            <!-- Formatting Rules -->
            <div :style="{ maxHeight:'250px', overflowY:'scroll', overflowX:'hidden' }" class="border mb-3 py-4 px-2">

                <!-- Rules -->
                <Row :gutter="4" class="border-bottom-dashed mx-0 mt-0 mb-2 pb-2" v-for="(formatting_rule, index) in formatting_rules" :key="index">

                    <!-- Formatting Name -->
                    <Col :span="6">
                        
                        <span>{{ formatting_rule.name }}</span>
                    
                    </Col>
                    
                    <!-- Formatting Comment -->    
                    <Col :span="14">

                        <span>{{ formatting_rule.comment }}</span>
                    
                    </Col>
                            
                    <Col :span="4">

                        <!-- Add Rule Button  -->
                        <Button @click.native="handleSelectedFormattingRule(formatting_rule)">
                            <Icon type="ios-add" :size="20" />
                            <span>Add</span>
                        </Button>

                    </Col>

                </Row>
            
            </div>

            <!-- Footer -->
            <template v-slot:footer>
                <div class="clearfix">
                    <Button @click.native="closeModal()" class="float-right">Close</Button>
                </div>
            </template>

        </Modal>
    </div>
</template>
<script>
    
    import modalMixin from './../../../../../../../../../../../../../components/_mixins/modal/main.vue';

    export default {
        mixins: [modalMixin],
        props: {
            formattingRules: {
                type: Array,
                default:() => []
            }
        },
        data(){
            return{
                formatting_rules:[
                    {
                        name: 'Capitalize',
                        type: 'capitalize',
                        comment: 'Capitalize the first letter of each word'
                    },
                    {
                        name: 'Uppercase',
                        type: 'uppercase',
                        comment: 'Set every character to uppercase'
                    },
                    {
                        name: 'Lowercase',
                        type: 'lowercase',
                        comment: 'Set every character to lowercase'
                    },
                    {
                        name: 'Trim',
                        type: 'trim',
                        comment: 'Remove whitespace at the start and end of the target value'
                    },
                    {
                        name: 'Trim (Left)',
                        type: 'trim_left',
                        comment: 'Remove whitespace at the start of the target value'
                    },
                    {
                        name: 'Trim (Right)',
                        type: 'trim_right',
                        comment: 'Remove whitespace at the end of the target value'
                    },
                    {
                        name: 'Limit',
                        type: 'limit',
                        value: '10',
                        comment: 'Limit the number of characters of the target value'
                    },
                    {
                        name: 'Substr',
                        type: 'substr',
                        value: '5',
                        value_2: '10',
                        comment: 'Returns the portion of the target value specified by the start and length parameters'
                    },
                    {
                        name: 'Remove Letters',
                        type: 'remove_letters',
                        comment: 'Remove numbers from the target value e.g "abc123def" into "123"'
                    },
                    {
                        name: 'Remove Numbers',
                        type: 'remove_numbers',
                        comment: 'Remove letters from the target value e.g "abc123def" into "abcdef"'
                    },
                    {
                        name: 'Remove Symbols',
                        type: 'remove_symbols',
                        comment: 'Remove symbols from the target value. It will remove everything except letters, numbers and spaces e.g "+26771234567" into "26771234567"'
                    },
                    {
                        name: 'Remove Spaces',
                        type: 'remove_spaces',
                        comment: 'Remove white spaces from the target value e.g "Remove spaces here" into "Removespaceshere"'
                    },
                    {
                        name: 'Replace With',
                        value: 'That',
                        value_2: 'This',
                        type: 'replace_with',
                        comment: 'Replace every occurence of a value with another value within the target value e.g replace "that" with "this" in the sentence "We love to play with that"'
                    },
                    {
                        name: 'Replace First With',
                        value: 'That',
                        value_2: 'This',
                        type: 'replace_first_with',
                        comment: 'Replace the first occurence of a value with another value within the target value e.g replace "that" with "this" in the sentence "We love to play with that"'
                    },
                    {
                        name: 'Replace Last With',
                        value: 'That',
                        value_2: 'This',
                        type: 'replace_last_with',
                        comment: 'Replace the last occurence of a value with another value within the target value e.g replace "that" with "this" in the sentence "We love to play with that"'
                    },
                    {
                        name: 'Plural Or Singular',
                        type: 'plural_or_singular',
                        value: 'child',
                        comment: 'Convert a string to its plural or singular form based on the target value e.g return "child" if the target value is "1" and "children" if the target value is greater than "1"'
                    },
                    {
                        name: 'Random String',
                        type: 'random_string',
                        value: '40',
                        comment: 'Generate a random string of the specified length e.g "40" will return a random 40 character string'
                    },
                    {
                        value: '',
                        name: 'Custom Format',
                        type: 'custom_format',
                        comment: 'Formats the target value using custom code for increased flexibility'
                    }
                ]
            }
        },
        methods: {
            handleSelectedFormattingRule(formatting_rule){

                var formatting_rule = Object.assign({}, formatting_rule);

                //  Set the id
                formatting_rule['id'] = this.generateFormattingRuleId();

                //  Set the active state details
                formatting_rule['active'] = {
                    text: true,
                    code_editor_text: '',
                    code_editor_mode: false
                }

                //  If we have the value property
                if( formatting_rule['value'] ){

                    //  Rebuild the value property structure
                    formatting_rule['value'] = {
                        text: formatting_rule['value'],
                        code_editor_text: '',
                        code_editor_mode: false
                    }

                }

                //  If we have the second value property
                if( formatting_rule['value_2'] ){

                    //  Rebuild the second value property structure
                    formatting_rule['value_2'] = {
                        text: formatting_rule['value_2'],
                        code_editor_text: '',
                        code_editor_mode: false
                    }

                }

                //  Set the color
                formatting_rule['hexColor'] = '#CECECE';

                //  Add formatting rule
                this.formattingRules.push(formatting_rule);

                this.$Message.success({
                    content: 'Formatting rule created!',
                    duration: 6
                });

                //  Close the modal
                this.closeModal();

            },
            generateFormattingRuleId(){
                return 'formatting_rule_' + Date.now();
            }
        }
    }
</script>
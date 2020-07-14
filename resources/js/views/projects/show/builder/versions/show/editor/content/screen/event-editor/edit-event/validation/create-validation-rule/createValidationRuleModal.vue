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
            title="Add Validation Rule"
            @on-visible-change="detectClose">
            
            <!-- Header -->
            <Row :gutter="4" class="bg-grey-light m-0 p-2">
                
                <Col :span="10">
                    <span class="font-weight-bold text-dark">Rule</span>
                </Col>
                
                <Col :span="14">
                    <span class="font-weight-bold text-dark">Example Disclaimer</span>
                </Col>

            </Row>

            <!-- Validation Rules -->
            <div :style="{ maxHeight:'250px', overflowY:'scroll', overflowX:'hidden' }" class="border mb-3 p-2">

                <!-- Rules -->
                <Row :gutter="4" class="mx-0 mt-0 mb-2" v-for="(validation_rule, index) in validation_rules" :key="index">

                    <!-- Validation Name -->
                    <Col :span="10">
                        
                        <span>{{ validation_rule.name }}</span>
                    
                    </Col>
                    
                    <!-- Validation Error Message -->    
                    <Col :span="10">

                        <Input v-model="validation_rule.error_msg" type="text" :disabled="true"></Input>
                    
                    </Col>
                            
                    <Col :span="4">

                        <!-- Add Rule Button  -->
                        <Button @click.native="handleSelectedValidationRule(validation_rule)">
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
            validationRules: {
                type: Array,
                default:() => []
            }
        },
        data(){
            return{
                validation_rules:[
                    {
                        name: 'Only Letters',
                        type: 'only_letters',
                        error_msg: 'Please enter letters only (spaces allowed)',
                        comment: 'Makes sure that the target value only contains letters and nothing else e.g "abc", "ABC" and "A B C". White spaces are also allowed.'
                    },
                    {
                        name: 'Only Numbers',
                        type: 'only_numbers',
                        error_msg: 'Please enter numbers only (spaces allowed)',
                        comment: 'Makes sure that the target value only contains numbers and nothing else e.g "123" and "1 2 3". White spaces are also allowed.'
                    },
                    {
                        name: 'Only Letters And Numbers',
                        type: 'only_letters_and_numbers',
                        error_msg: 'Please enter letters and numbers only (spaces allowed)',
                        comment: 'Makes sure that the target value only contains letters and numbers and nothing else e.g "abc123", "ABC123" and "A B C 1 2 3". White spaces are also allowed.'
                    },
                    {
                        value: '3',
                        name: 'Minimum Character Length',
                        type: 'minimum_characters',
                        error_msg: 'Please enter 3 or more characters',
                        comment: 'Makes sure that the target value contains atleast the specified number of characters e.g "3" means atleast 3 characters must be provided. This means "abc" is valid while "a" and "ab" are not valid. Remember that we also count whitespaces e.g "a b" is also 3 characters.'
                    },
                    {
                        value: '3',
                        name: 'Maximum Character Length',
                        type: 'maximum_characters',
                        error_msg: 'Please enter no more than 3 characters',
                        comment: 'Makes sure that the target value contains no more than the specified number of characters e.g "3" means no more than 3 characters must be provided. This means "a", "ab" and "abc" are valid while "abcd" is not valid. Remember that we also count whitespaces e.g "a b" is also 3 characters.'
                    },
                    {
                        value: '3',
                        name: 'Equal To Character Length',
                        type: 'equal_to_characters',
                        error_msg: 'Please enter exactly 3 characters',
                        comment: 'Makes sure that the target value contains the exact specified number of characters e.g "3" exactly 3 characters must be provided. This means "a", "ab" and "abcd" are valid while "abc" is valid. Remember that we also count whitespaces e.g "a b" is also 3 characters.'
                    },
                    {
                        name: 'Validate Email',
                        type: 'validate_email',
                        error_msg: 'Please provide a valid email address e.g example@gmail.com',
                        comment: 'Makes sure that the target value contains a valid email address e.g joe@gmail.com or sarah@example.com.'
                    },
                    {
                        name: 'Validate Mobile Number',
                        type: 'validate_mobile_number',
                        error_msg: 'Please provide a valid Botswana phone number e.g 71234567',
                        comment: 'Makes sure that the target value contains a valid phone number e.g 71234567.'
                    },
                    {
                        name: 'Validate Date Format (DD/MM/YYYY)',
                        type: 'valiate_date_format',
                        error_msg: 'Please enter a valid date (DD/MM/YYYY) e.g 02/08/2020',
                        comment: 'Makes sure that the target value contains a valid date format (DD/MM/YYYY) e.g e.g 02/08/2020.'
                    },
                    {
                        value: '3',
                        name: 'Equal To (=)',
                        type: 'equal_to',
                        error_msg: 'Please enter the character 3',
                        comment: 'Makes sure that the target value is an exact matching character e.g "3" means that the value provided must be exactly "3".'
                    },
                    {
                        value: '3',
                        name: 'Not Equal To',
                        type: 'not_equal_to',
                        error_msg: 'Please enter any character except 3',
                        comment: 'Makes sure that the target value is not an exact matching character e.g "3" means that the value provided must not be "3".'
                    },
                    {
                        value: '3',
                        name: 'Less Than (<)',
                        type: 'less_than',
                        error_msg: 'Please enter numbers less than 3',
                        comment: 'Makes sure that the target value is less than the given number e.g "3" means that the value provided must be strictly less than "3".'
                    },
                    {
                        value: '3',
                        name: 'Less Than Or Equal (<=)',
                        type: 'less_than_or_equal',
                        error_msg: 'Please enter numbers less than or equal to 3',
                        comment: 'Makes sure that the target value is less than or equal to the given number e.g "3" means that the value provided must be less than or equal to "3".'
                    },
                    {
                        value: '3',
                        name: 'Greater Than (>)',
                        type: 'greater_than',
                        error_msg: 'Please enter numbers greater than 3',
                        comment: 'Makes sure that the target value is greater than the given number e.g "3" means that the value provided must be strictly greater than "3".'
                    },
                    { 
                        value: '3',
                        name: 'Greater Than Or Equal (>=)',
                        type: 'greater_than_or_equal',
                        error_msg: 'Please enter numbers greater than or equal to 3',
                        comment: 'Makes sure that the target value is greater than or equal to the given number e.g "3" means that the value provided must be greater than or equal to "3".'
                    },
                    {
                        value: '1',
                        value_2: '10',
                        name: 'In Between (Including Inputs)',
                        type: 'in_between_including',
                        error_msg: 'Please enter numbers between 1 and 10 (including 1 and 10)',
                        comment: 'Makes sure that the target value is a number that is in-between or equal to any of the given minimum and maximum values e.g min="3" and max="5" means that the value provided must "3", "4" or "5" to be valid.'
                    },
                    {
                        value: '1',
                        value_2: '10',
                        name: 'In Between (Excluding Inputs)',
                        type: 'in_between_excluding',
                        error_msg: 'Please enter numbers between 1 and 10 (excluding 1 and 10)',
                        comment: 'Makes sure that the target value is a number that is in-between and not equal to any of the given minimum and maximum values e.g min="3" and max="5" means that the value provided must only be "4" to be valid.'
                    },
                    {
                        name: 'No Spaces',
                        type: 'no_spaces',
                        error_msg: 'Do not use spaces',
                        comment: 'Makes sure that the target value does not contain any white spaces e.g "abc123" is valid while "abc 123" is not valid since we have white space.'
                    },
                    {
                        rule: {
                            text: '/^[a-zA-Z0-9]+$/',
                            code_editor_text: '',
                            code_editor_mode: false
                        },
                        name: 'Custom Regex',
                        type: 'custom_regex',
                        error_msg: 'Custom regex validation error',
                        comment: 'Makes sure that the target value matches the given Regex Expression e.g if the given pattern is "/[a-zA-Z0-9]+/" then this will only be valid for letters and numbers only.'
                    }
                ]
            }
        },
        methods: {
            handleSelectedValidationRule(validation_rule){

                var validation_rule = Object.assign({}, validation_rule);

                //  Set the id
                validation_rule['id'] = this.generateValidationRuleId();

                //  Set the active state details
                validation_rule['active'] = {
                    text: true,
                    code_editor_text: '',
                    code_editor_mode: false
                }

                //  If we have the value property
                if( validation_rule['value'] ){

                    //  Rebuild the value property structure
                    validation_rule['value'] = {
                        text: validation_rule['value'],
                        code_editor_text: '',
                        code_editor_mode: false
                    }

                }

                //  If we have the second value property
                if( validation_rule['value_2'] ){

                    //  Rebuild the value property structure
                    validation_rule['value_2'] = {
                        text: validation_rule['value_2'],
                        code_editor_text: '',
                        code_editor_mode: false
                    }

                }

                //  Rebuild the error message property structure
                validation_rule['error_msg'] = {
                    text: validation_rule['error_msg'],
                    code_editor_text: '',
                    code_editor_mode: false
                },

                //  Set the color
                validation_rule['hexColor'] = '#CECECE';

                //  Add validation rule
                this.validationRules.push(validation_rule);

                this.$Message.success({
                    content: 'Validation rule created!',
                    duration: 6
                });

                //  Close the modal
                this.closeModal();

            },
            generateValidationRuleId(){
                return 'validation_rule_' + Date.now();
            }
        }
    }
</script>
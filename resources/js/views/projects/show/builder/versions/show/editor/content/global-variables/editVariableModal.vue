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
            width="650"
            title="Edit Variable"
            v-model="modalVisible"
            @on-visible-change="detectClose">
                        
            <!-- Heading -->
            <Divider orientation="left" class="font-weight-bold">Variable Details</Divider>

            <Alert show-icon>Editing "<span class="font-weight-bold">{{ globalVariableForm.name }}</span>"</Alert>

            <!-- Edit String Value -->
            <template v-if="globalVariableForm.type == 'String'">
                <span class="font-weight-bold text-dark">Variable Value:</span>
                <Input type="textarea" v-model="globalVariableForm.value.string" v-focus="'input'"
                        @keyup.enter.native="handleSubmit()" placeholder="Variable text...">
                </Input>
            </template>

            <template v-if="globalVariableForm.type == 'Custom'">

                <!-- Edit Custom Code Value -->
                <customEditor 
                    :useCodeEditor="true"
                    :codeContent="globalVariableForm.value.code"
                    @codeChange="globalVariableForm.value.code = $event"
                    sampleCodeTemplate="ussd_service_global_variable_custom_code_sample">
                </customEditor>
            
            </template>

            <div class="mt-2">

                <Poptip trigger="hover" placement="top" word-wrap width="250" 
                        content="The value will be saved to the database and made available for future sessions">

                    <Checkbox v-model="globalVariableForm.is_global" class="mb-2">
                        <span class="font-weight-bold">Save for next session</span>
                    </Checkbox>

                </Poptip>

                <Poptip trigger="hover" placement="top" word-wrap width="250" 
                        content="The value cannot be overiden once it is set (It is a constant)">

                    <Checkbox v-model="globalVariableForm.is_constant" class="mb-2">
                        <span class="font-weight-bold">Make constant</span>
                    </Checkbox>
                    
                </Poptip>

            </div>

            <!-- Footer -->
            <template v-slot:footer>
                <div class="clearfix">
                    <Button type="primary" @click.native="handleSubmit()" class="float-right">Done</Button>
                    <Button @click.native="closeModal()" class="float-right mr-2">Cancel</Button>
                </div>
            </template>

        </Modal>
    </div>
</template>
<script>

    //  Get the custom editor
    import customEditor from './../../../../../../../../../components/_common/wysiwygEditors/customEditor.vue';

    //  Get the modal mixin data
    import modalMixin from './../../../../../../../../../components/_mixins/modal/main.vue';

    //  Get the custom mixin file
    var customMixin = require('./../../../../../../../../../mixin.js').default;

    export default {
        mixins: [modalMixin, customMixin],
        components: { customEditor },
        props: {
            index: {
                type: Number,
                default: null 
            },
            variable: {
                type: Object,
                default: () => {}
            },
            version: {
                type: Object,
                default: () => {}
            }
        },
        data(){
            return {
                globalVariableForm: null
            }
        },
        methods: {
            getGlobalVariableForm(){
                
                var globalVariable = Object.assign({}, this.variable);

                return _.cloneDeep( globalVariable );

            },
            /** Note the closeModal() method is imported from the
             *  modalMixin file. It handles the closing process 
             *  of the modal
             */
            handleSubmit(){

                console.log('this.version.builder.global_variables');
                console.log(this.version.builder.global_variables);
                console.log('this.index');
                console.log(this.index);
                console.log('this.globalVariableForm');
                console.log(this.globalVariableForm);

                this.$set(this.version.builder.global_variables, this.index, this.globalVariableForm);
                
                this.closeModal();

                this.$Message.success({
                    content: 'Varialble Updated!',
                    duration: 6
                });

            }
        },
        created(){
            this.globalVariableForm = this.getGlobalVariableForm();
        }
    }
</script>
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

            <Alert show-icon>Editing "<span class="font-weight-bold">{{ variable.name }}</span>"</Alert>

            <!-- Edit String Value -->
            <template v-if="variable.type == 'String'">
                <span class="font-weight-bold text-dark">Variable Value:</span>
                <Input type="textarea" v-model="variable.value.string" v-focus="'input'"
                        @keyup.enter.native="handleSubmit()" placeholder="Variable text...">
                </Input>
            </template>

            <!-- Edit Custom Code Value -->
            <customEditor 
                v-else
                :useCodeEditor="true"
                :codeContent="variable.value.code"
                @codeChange="variable.value.code = $event"
                sampleCodeTemplate="ussd_service_global_variable_custom_code_sample">
            </customEditor>

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
            variable: {
                type: Object,
                default: () => {}
            },
            builder: {
                type: Object,
                default: () => {}
            }
        },
        data(){
            return {
                
            }
        },
        methods: {

            /** Note the closeModal() method is imported from the
             *  modalMixin file. It handles the closing process 
             *  of the modal
             */
            handleSubmit(){
                
                this.closeModal();

                this.$Message.success({
                    content: 'Varialble Updated!',
                    duration: 6
                });

            }
        }
    }
</script>
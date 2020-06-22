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
            okText="Clone" 
            @on-ok="clone()"
            cancelText="Cancel"
            title="Clone Screen"
            v-model="modalVisible"
            @on-visible-change="detectClose">
               
            This is the modal content

        </Modal>
    </div>
</template>
<script>

    import modalMixin from './main.vue';

    export default {
        mixins: [ modalMixin ],
        data(){
            return{
                
            }
        },
        methods: {
            /** Note the closeModal() method is imported from the
             *  modalMixin file. It handles the closing process 
             *  of the modal
             */
            clone(){
                this.$emit('clone');
                this.closeModal();
            }
        }
    }
</script>
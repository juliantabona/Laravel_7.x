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
            title="Import Event"
            v-model="modalVisible"
            @on-visible-change="detectClose">

            <div :style="{ minHeight: '220px' }">
                
                <!-- Global Events List For Import -->
                <eventsManager 
                    :events="version.builder.global_events" :version="version" 
                    :usingImportEventManager="true" @import="handleImport($event)">
                </eventsManager>
                
            </div>

            <!-- Footer -->
            <template v-slot:footer>
                <div class="clearfix">
                    <Button @click.native="closeModal()" class="float-right mr-2">Cancel</Button>
                </div>
            </template>

        </Modal>
    </div>
</template>
<script>
    
    import modalMixin from './../../../../../../../../../../../components/_mixins/modal/main.vue';

    export default {
        mixins: [modalMixin],
        props: {
            events: {
                type: Array,
                default: () => []
            },
            version: {
                type: Object,
                default: null
            }
        },
        data(){

            return {

            }
        },
        methods: {
            handleImport(event){

                /** This "event" was first emited from the "single-event" component
                 *  that is embedded within this "eventsManager" component. We need
                 *  to re-emit from this "eventsManager" that is embedded on the
                 *  "importGlobalEventModal" component. This "importGlobalEventModal"
                 *  is embedded within another "eventsManager".
                 * 
                 *  Here is an illustration:
                 * 
                 *  "eventsManager 1" -> "importGlobalEventModal" -> "eventsManager 2" -> "single-event"
                 * 
                 *   Note that "eventsManager 2" is the "<eventsManager ...></eventsManager>" tag above 
                 */
                this.$emit('import', event);

                /** Note the closeModal() method is imported from the
                 *  modalMixin file. It handles the closing process 
                 *  of the modal
                 */
                this.closeModal();
            },
        },
        created(){
            
        }
    }
</script>
<script>

    export default {
        data(){
            return{
                modalVisible: false
            }
        },
        watch: {

            //  Watch for changes on the visibility state
            visibility: {
                handler: function (val, oldVal) {
                    
                    if(val){

                        //  Hide the modal
                        this.closeModal();

                    }

                }
            }
        },
        methods: {
            closeModal(){
                /** By setting modalVisible = false, we also trigger the detectClose() method
                 *  since the modal has the event @on-visible-change="detectClose" to detect
                 *  any changes of the "modalVisible". The "detectClose()" method would then
                 *  notify the parent on the changes of the modal visibility.
                 */
                this.modalVisible = false;
            },
            detectClose(){
                
                var self = this;

                /** Wait for 1/2 (half) a second then update the parent component of the changes.
                 *  This is so that we can allow the modal to fade out properly before we remove
                 *  it from the DOM. If we don't use setTimeout, the modal is removed without a
                 *  transition effect.
                 */
                setTimeout(function(){

                    //  Notify the parent on value of modal visibility
                    self.$emit('visibility', self.modalVisible);
                
                }, 500);

            }
        },
        mounted: function () {
            this.modalVisible = true;
        }
    }
</script>
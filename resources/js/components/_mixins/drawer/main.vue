<script>

    export default {
        data(){
            return{
                drawerVisible: false
            }
        },
        watch: {

            //  Watch for changes on the visibility state
            visibility: {
                handler: function (val, oldVal) {
                    
                    if(val){

                        //  Hide the drawer
                        this.closeDrawer();

                    }

                }
            }
        },
        methods: {
            closeDrawer(){
                /** By setting drawerVisible = false, we also trigger the detectClose() method
                 *  since the drawer has the event @on-visible-change="detectClose" to detect
                 *  any changes of the "drawerVisible". The "detectClose()" method would then
                 *  notify the parent on the changes of the drawer visibility.
                 */
                this.drawerVisible = false;
            },
            detectClose(){
                
                var self = this;

                /** Wait for 1/2 (half) a second then update the parent component of the changes.
                 *  This is so that we can allow the drawer to fade out properly before we remove
                 *  it from the DOM. If we don't use setTimeout, the drawer is removed without a
                 *  transition effect.
                 */
                setTimeout(function(){

                    //  Notify the parent on value of drawer visibility
                    self.$emit('visibility', self.drawerVisible);
                
                }, 500);

            }
        },
        mounted: function () {
            this.drawerVisible = true;
        }
    }
</script>
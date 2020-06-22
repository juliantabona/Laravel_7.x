<template>

    <!-- Form -->
    <Form ref="commentForm" :model="commentForm" :rules="commentFormRules" class="mx-0 my-0">

        <span class="d-block font-weight-bold mb-1">Comment:</span>

        <!-- Comment Input -->
        <FormItem prop="comment" class="mb-0">
            <Input  type="textarea" v-model="commentForm.comment" placeholder="Comment..."
                    :maxlength="max" show-word-limit @keyup.native="handleSubmit()">
            </Input>
        </FormItem>
        
    </Form>

</template>

<script>

    export default {
        props: {
            value: {
                type: String,
                default: null
            },
            max: {
                type: Number,
                default: 500
            }
        },
        data(){
            return {
                commentForm: null,
                commentFormRules: {
                    comment: [
                        { min: 3, message: 'Comment is too short', trigger: 'change' },
                        { max: this.max, message: 'Comment is too long', trigger: 'change' }
                    ],
                }
            }
        },
        methods: {
            getCommentForm(){

                /** Set the default form details
                 * 
                 * NOTE: this.value exists since we are using v-model on the parent component 
                 */ 
                return {
                    comment: this.value
                }
            },
            handleSubmit(){
                //  Validate the comment form
                this.$refs['commentForm'].validate((valid) => 
                {   
                    //  If the validation failed
                    if (valid) {

                        //  Notify parent of the new value
                        this.$emit('input', this.commentForm.comment);

                    }else{
                        
                        //  Notify parent of the new value
                        this.$emit('input', '');
                    }
                })
            },
        },
        created(){
            //  Get the comment form
            this.commentForm = this.getCommentForm();
        },
        mounted() {

            //  When the DOM Form is ready, Validate the comment form
            this.handleSubmit();
            
        },
    }
</script>

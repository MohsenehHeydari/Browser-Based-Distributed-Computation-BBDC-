<template>
    <div class="kt-login">
        <div >
            <p class="login-box-msg">Register a new membership</p>

            <form action="" method="post" @submit.prevent="submitForm" @keydown="deleteError($event.target.name)">
                <div class="input-group mb-3">
                    <input type="text" name="name" class="form-control" placeholder="Full name" v-model="form.name">
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-user"></span>
                        </div>
                    </div>
                    <div v-if="errors.name !== undefined" class="error invalid-feedback" v-for="error in errors.name">
                        {{error}}
                    </div>
                </div>
                <div class="input-group mb-3">
                    <input type="email" class="form-control" placeholder="Email" v-model="form.email" name="email">
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-envelope"></span>
                        </div>
                    </div>
                    <div v-if="errors.email !== undefined" class="error invalid-feedback" v-for="error in errors.email">
                        {{error}}
                    </div>
                </div>
                <div class="input-group mb-3">
                    <input v-model="form.password" name="password" type="password" class="form-control" placeholder="Password">
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-lock"></span>
                        </div>
                    </div>
                    <div v-if="errors.password !== undefined" class="error invalid-feedback"
                         v-for="error in errors.password">{{error}}
                    </div>
                </div>
                <div class="input-group mb-3">
                    <input type="password" name="password_confirmation" v-model="form.password_confirmation" class="form-control" placeholder="Retype password">
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-lock"></span>
                        </div>
                    </div>
                    <div v-if="errors.password_confirmation !== undefined" class="error invalid-feedback"
                         v-for="error in errors.password_confirmation">{{error}}
                    </div>
                </div>
                <div class="row">
                    <div class="col-8">
                        <div class="icheck-primary">
                            <input type="checkbox" id="agree" name="agree" v-model="form.agree" value="agree">
                            <label for="agree">
                                I agree to the <a href="#">terms</a>
                            </label>
                        </div>
                        <div v-if="errors.agree !== undefined"  v-for="error in errors.agree" class="error invalid-feedback" style="display: block;" >{{error}}</div>
                    </div>
                    <!-- /.col -->
                    <div class="col-4">
                        <button type="submit" class="btn btn-primary btn-block">Register</button>
                    </div>
                    <!-- /.col -->
                </div>
            </form>

            <a @click.prevent="$emit('change_component','login')" class="text-center">I already have a membership</a>
        </div>
    </div>
</template>

<script>
    import axios from 'axios';
    export default {
        data() {
            return {
                form: {
                    name: '',
                    email: '',
                    password: '',
                    password_confirmation: '',
                    agree: null,
                },
                errors: {}
            }
        },
        inject: ['baseUrl'],
        props:['type'],
        methods: {
            submitForm(event) {
                this.errors = [];
                let vm = this;
                let url = this.baseUrl+ '/' + this.type + '/register';
                let el = event.target;
                let form_data = new FormData(el);
                form_data.append('_method', 'post');
                axios.post(url,form_data)
                    .then(response => {
                        if (response.data.redirect_path !== undefined) {
                            window.location = response.data.redirect_path;
                        }
                    })
                    .catch(error => {

                        if (error.response !== undefined && error.response.status === 422) {
                            if (error.response.data.errors !== undefined) {
                                vm.errors = error.response.data.errors;
                            }
                        }
                    })
            },
            deleteError(error) {
                if (this.errors[error] !== undefined) {
                    delete this.errors[error];
                }

            },
        }
    }
</script>

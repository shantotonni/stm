<template>
  <div class="wrapper-page d-flex justify-content-center align-items-center min-vh-100 bg-light">
    <div class="card shadow-lg rounded-3 p-4" style="max-width: 420px; width: 100%;">
      <h4 class="text-center text-primary mb-3">🔒 Change Password</h4>
      <p class="text-muted text-center mb-4">
        For your security, you must change your password first.<br>
        <small>⚠ Do not share your password with anyone.</small>
      </p>

      <ValidationObserver v-slot="{ handleSubmit }">
        <form @submit.prevent="handleSubmit(onSubmit)">

          <!-- Old Password -->
          <ValidationProvider name="Old Password" rules="required|min:6" v-slot="{ errors }">
            <div class="mb-3">
              <label class="form-label">Old Password</label>
              <input type="password" v-model="oldPassword" class="form-control"
                     :class="{ 'is-invalid': errors[0] }" placeholder="Enter old password">
              <div class="invalid-feedback">{{ errors[0] }}</div>
            </div>
          </ValidationProvider>

          <!-- New Password -->
          <ValidationProvider name="New Password" vid="newPassword" rules="required|min:6" v-slot="{ errors }">
            <div class="mb-3">
              <label class="form-label">New Password</label>
              <input type="password" v-model="newPassword" class="form-control"
                     :class="{ 'is-invalid': errors[0] }" placeholder="Enter new password">
              <div class="invalid-feedback">{{ errors[0] }}</div>
            </div>
          </ValidationProvider>

          <!-- Confirm Password -->
          <ValidationProvider name="Confirm Password" rules="required|confirmed:newPassword" v-slot="{ errors }">
            <div class="mb-3">
              <label class="form-label">Confirm Password</label>
              <input type="password" v-model="confirmPassword" class="form-control"
                     :class="{ 'is-invalid': errors[0] }" placeholder="Re-enter new password">
              <div class="invalid-feedback">{{ errors[0] }}</div>
            </div>
          </ValidationProvider>

          <!-- Submit -->
          <div class="d-grid mt-4">
            <button type="submit" class="btn btn-primary btn-lg">
              Update Password
            </button>
          </div>

        </form>
      </ValidationObserver>
    </div>
  </div>
</template>
<script>
import {Common} from '../../mixins/common'
import moment from "moment";

export default {
  mixins: [Common],
  data() {
    return {
      oldPassword: "",
      newPassword: "",
      confirmPassword: ""
    }
  },
  computed: {
    now() {
      return moment()
    }
  },
  mounted() {
    document.title = 'Change Password | Medical Survey';
  },
  methods: {
    onSubmit() {
      this.$store.commit('submitButtonLoadingStatus', true);
      this.axiosPost('change-password', {
        oldPassword: this.oldPassword,
        newPassword: this.newPassword,
        newPassword_confirmation: this.confirmPassword,
      }, (response) => {
        if (response.status === "success") {
          localStorage.setItem("is_change_password", "Y");
          this.successNoti('Successfully logged in.');
          this.$store.commit('submitButtonLoadingStatus', false);
          this.redirect(this.mainOrigin + 'dashboard')
        }
      }, (error) => {
        this.errorNoti(error);
        this.$store.commit('submitButtonLoadingStatus', false);
      })
    }
  }
}
</script>

<style scoped>
.wrapper-page {
  background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
}
.card {
  background: #fff;
  border: none;
}
.wrapper-page {
  margin: .5% auto;
  max-width: 487px;
  position: relative;
}
</style>

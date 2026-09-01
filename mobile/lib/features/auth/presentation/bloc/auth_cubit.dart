import 'package:flutter_bloc/flutter_bloc.dart';
import '../../data/auth_repository.dart';

abstract class AuthState {}
class AuthInitial extends AuthState {}
class AuthLoading extends AuthState {}
class AuthSuccess extends AuthState {
  final String role;
  AuthSuccess(this.role);
}
class AuthFailure extends AuthState {
  final String message;
  AuthFailure(this.message);
}
class AuthUnauthenticated extends AuthState {}

class AuthCubit extends Cubit<AuthState> {
  final AuthRepository repository;

  AuthCubit(this.repository) : super(AuthInitial());

  Future<void> login(String email, String password) async {
    emit(AuthLoading());
    final role = await repository.login(email, password);
    if (role != null) {
      emit(AuthSuccess(role));
    } else {
      emit(AuthFailure("Identifiants incorrects ou erreur réseau."));
    }
  }

  Future<void> register(String name, String email, String phone, String password, String passwordConfirmation) async {
    emit(AuthLoading());
    try {
      final role = await repository.register(name, email, phone, password, passwordConfirmation);
      if (role != null) {
        emit(AuthSuccess(role));
      } else {
        emit(AuthFailure("Erreur lors de l'inscription."));
      }
    } catch (e) {
      emit(AuthFailure(e.toString().replaceAll("Exception: ", "")));
    }
  }

  Future<void> logout() async {
    emit(AuthLoading());
    await repository.logout();
    emit(AuthUnauthenticated());
  }
}

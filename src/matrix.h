#ifndef MATRIX_H
#define MATRIX_H

#include <stddef.h>

namespace digit_recognizer
{

template <typename T>
class matrix
{
public:
	matrix(size_t height, size_t width);
	matrix(size_t height);
	matrix(matrix const &matrix);
	~matrix();

	size_t height() const;
	size_t width() const;

	void reset(size_t dimension); // for square matrix
	void reset(size_t height, size_t width);
	void reset(matrix<T> const &);

	T &operator()(size_t row, size_t col);
	T const &operator()(size_t row, size_t col) const;

	matrix<T> &operator=(matrix<T> const &rhs);

	matrix<T> const operator*(matrix<T> const &rhs) const;

private:
	size_t height_;
	size_t width_;
	T *elements_;

private:
	void init(size_t height, size_t width);
	void free();
};

template <typename T>
matrix<T>::matrix(size_t height, size_t width)
{
	init(height, width);
}

template <typename T>
matrix<T>::matrix(size_t height)
{
	init(height, height);
}

template <typename T>
matrix<T>::~matrix()
{
	free();
}

template <typename T>
size_t
matrix<T>::height() const
{
	return height_;
}

template <typename T>
size_t
matrix<T>::width() const
{
	return width_;
}

template <typename T>
void matrix<T>::reset(size_t height, size_t width)
{
	free();
	init(height, width);
}

template <typename T>
void matrix<T>::reset(size_t height)
{
	reset(height, height);
}

template <typename T>
void matrix<T>::reset(matrix<T> const &rhs)
{
	if (this == &rhs)
		return;
	reset(rhs.height(), rhs.width());
	*this = rhs;
}

template <typename T>
matrix<T> &
matrix<T>::operator=(matrix<T> const &rhs)
{
	if (this == &rhs)
	{
		return *this;
	}

	free();
	init(rhs.height(), rhs.width());
	matrix<T> &lhs = *this;
	for (size_t i = 0; i < height_; i++)
	{
		for (size_t j = 0; j < width_; j++)
		{
			lhs(i, j) = rhs(i, j);
		}
	}
}

template <typename T>
void matrix<T>::init(size_t height, size_t width)
{
	height_ = height;
	width_ = width;
	elements_ = new T[height_ * width_];
	for (size_t i = 0; i < height_ * width_; i++) {
		elements_[i] = 0;
	}
}

template <typename T>
void matrix<T>::free()
{
	delete[] elements_;
}

template <typename T>
T &matrix<T>::operator()(size_t row, size_t col)
{
	return elements_[row * width_ + col];
}

template <typename T>
T const &
matrix<T>::operator()(size_t row, size_t col) const
{
	return elements_[row * width_ + col];
}

template <typename T>
matrix<T> const
	matrix<T>::operator*(matrix<T> const &rhs) const
{
	matrix<T> const &lhs = *this;
	matrix<T> result(lhs.height(), rhs.width());
	for (size_t i = 0; i < lhs.height(); i++)
	{
		for (size_t k = 0; k < rhs.width(); k++)
		{
			for (size_t j = 0; j < lhs.width(); j++)
			{
				result(i, k) += lhs(i, j) * rhs(j, k);
			}
		}
	}
	return result;
}

} // namespace digit_recognizer
#endif